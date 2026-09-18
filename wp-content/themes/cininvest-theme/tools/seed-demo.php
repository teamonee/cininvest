<?php
/**
 * Сквозной демо-сидер для ручного теста платформы на реальном сервере.
 *
 * Запуск: wp eval-file wp-content/themes/cininvest-theme/tools/seed-demo.php
 * Откат:  wp eval-file wp-content/themes/cininvest-theme/tools/seed-demo-cleanup.php
 *
 * Что делает:
 *  1) создаёт 3 тестовых пользователя (cinematographer/investor/sponsor, cininvest_role);
 *  2) от кинематографиста создаёт проект (CPT project) и заполняет ACF-поля
 *     (сумма/цель, доли, команда, документы, вознаграждения, отзывы);
 *  3) от кинематографиста создаёт 4 заявки (CPT application) — по одной на каждый статус
 *     модерации (draft/submitted/pre_review/approved), с тем же набором meta-полей,
 *     который реально сохраняет inc/forms-application.php по всем 7 секциям формы;
 *  4) пополняет баланс инвестору и спонсору и проводит покупку долей/вознаграждений —
 *     строго через cininvest_gateway(), той же функцией, которую вызывают реальные
 *     admin-post обработчики (inc/ajax-shares.php), а не прямой записью в БД.
 *
 * Идемпотентность: пользователи ищутся по email, проект — по post_name, заявки и
 * вложения — по метке _cininvest_seed_key в postmeta, операции — по метке
 * 'seed' => CININVEST_SEED_TAG в JSON-колонке meta. Повторный запуск обновляет
 * ACF-поля/meta-поля заявок и пропускает уже проведённые операции/депозиты.
 *
 * Почему не вызываются cininvest_handle_buy_shares()/cininvest_handle_buy_perks() напрямую:
 * это admin-post хендлеры, заточенные под HTTP (nonce из $_POST, wp_die(), redirect+exit) —
 * в CLI-скрипте это сразу оборвёт выполнение. Их полезная нагрузка — ровно один вызов
 * cininvest_gateway()->charge(...) с тем же набором аргументов, который здесь и
 * воспроизведён. Это и есть "тот же путь, что у реальных форм", без HTTP-обвязки.
 */

if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) {
    fwrite(STDERR, "Запускать только через wp-cli:\n  wp eval-file wp-content/themes/cininvest-theme/tools/seed-demo.php\n");
    exit(1);
}

if (!function_exists('update_field') || !function_exists('get_field')) {
    WP_CLI::error('Плагин ACF (Advanced Custom Fields) не активен — без него нельзя заполнить поля проекта.');
}

if (!defined('CININVEST_SEED_TAG')) define('CININVEST_SEED_TAG', 'seed-demo-v1');

/* ---------------------------------------------------------------------
 * Хелперы
 * -------------------------------------------------------------------*/

/** Ищет уже проведённую сид-операцию по метке в JSON meta (идемпотентность для gateway-вызовов) */
function cininvest_seed_find_operation($user_id, $type, $project_id = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'cininvest_operations';
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table} WHERE user_id = %d AND type = %s ORDER BY id DESC", $user_id, $type
    ));
    foreach ($rows as $row) {
        $meta = $row->meta ? json_decode($row->meta, true) : [];
        if ((($meta['seed'] ?? null) === CININVEST_SEED_TAG)
            && ($project_id === null || (int) $row->project_id === (int) $project_id)) {
            return $row;
        }
    }
    return null;
}

/** Депозит через gateway, пропускается, если уже был проведён этим скриптом */
function cininvest_seed_ensure_deposit($user_id, $amount, $label) {
    $existing = cininvest_seed_find_operation($user_id, 'deposit');
    if ($existing) {
        WP_CLI::log("  {$label}: депозит уже проведён ранее (операция #{$existing->id}) — пропускаю.");
        return;
    }
    $result = cininvest_gateway()->deposit($user_id, $amount, ['seed' => CININVEST_SEED_TAG]);
    if (is_wp_error($result)) {
        WP_CLI::error("{$label}: не удалось пополнить баланс — " . $result->get_error_message());
    }
    WP_CLI::success("  {$label}: баланс пополнен на " . cininvest_money($amount) . " (операция #{$result}).");
}

/** Плейсхолдер-вложение (текстовый файл) для полей типа "file" в материалах/документах.
 *  Идемпотентно: ищет по метке _cininvest_seed_key, не плодит дубли при повторном запуске. */
function cininvest_seed_placeholder_attachment($key, $filename, $title, $content) {
    $found = get_posts([
        'post_type' => 'attachment', 'posts_per_page' => 1, 'post_status' => 'inherit',
        'meta_key' => '_cininvest_seed_key', 'meta_value' => $key,
    ]);
    if ($found) return $found[0]->ID;

    $upload = wp_upload_bits($filename, null, $content);
    if (!empty($upload['error'])) {
        WP_CLI::warning("Не удалось создать демо-файл {$filename}: {$upload['error']}");
        return 0;
    }
    $attachment_id = wp_insert_attachment([
        'post_mime_type' => 'text/plain',
        'post_title' => $title,
        'post_content' => '',
        'post_status' => 'inherit',
    ], $upload['file']);
    if (is_wp_error($attachment_id)) return 0;

    require_once ABSPATH . 'wp-admin/includes/image.php';
    $meta = wp_generate_attachment_metadata($attachment_id, $upload['file']);
    wp_update_attachment_metadata($attachment_id, $meta);
    update_post_meta($attachment_id, '_cininvest_seed_key', $key);
    return $attachment_id;
}

/** Тестовая заявка (CPT application) с тем же набором meta-полей, что реально сохраняет
 *  inc/forms-application.php::cininvest_handle_application(). Идемпотентно по _cininvest_seed_key. */
function cininvest_seed_ensure_application($key, $author_id, $applicant_type, $status, $project_name, $fields, $array_fields) {
    $found = get_posts([
        'post_type' => 'application', 'posts_per_page' => 1, 'post_status' => 'publish',
        'meta_key' => '_cininvest_seed_key', 'meta_value' => $key,
    ]);
    if ($found) {
        $app_id = $found[0]->ID;
    } else {
        $app_id = wp_insert_post([
            'post_type' => 'application',
            'post_title' => $project_name,
            'post_author' => $author_id,
            'post_status' => 'publish',
        ], true);
        if (is_wp_error($app_id)) {
            WP_CLI::warning("Не удалось создать заявку '{$key}': " . $app_id->get_error_message());
            return 0;
        }
        update_post_meta($app_id, '_cininvest_seed_key', $key);
    }

    // ровно тот же список meta-ключей, что сохраняет реальный обработчик заявки
    $fields['applicant_type'] = $applicant_type;
    $fields['project_name'] = $project_name;
    foreach ($fields as $f => $v) {
        update_post_meta($app_id, $f, $v);
    }
    foreach ($array_fields as $f => $v) {
        update_post_meta($app_id, $f, $v);
    }

    wp_set_object_terms($app_id, $status, 'application_status');
    return $app_id;
}

/* ---------------------------------------------------------------------
 * 1) Пользователи
 * -------------------------------------------------------------------*/
WP_CLI::log('== Пользователи ==');

$users = [
    'cinematographer' => ['email' => 'cinema@test.local', 'password' => 'CinemaSeed#2026', 'first' => 'Иван', 'last' => 'Съёмкин', 'phone' => '+7 900 000-00-01'],
    'investor'         => ['email' => 'investor@test.local', 'password' => 'InvestorSeed#2026', 'first' => 'Пётр', 'last' => 'Вкладов', 'phone' => '+7 900 000-00-02'],
    'sponsor'          => ['email' => 'sponsor@test.local', 'password' => 'SponsorSeed#2026', 'first' => 'Анна', 'last' => 'Спонсорова', 'phone' => '+7 900 000-00-03'],
];

$ids = [];
foreach ($users as $role => $u) {
    $existing = get_user_by('email', $u['email']);
    if ($existing) {
        $uid = $existing->ID;
        WP_CLI::log("  {$u['email']} уже существует (ID {$uid}), пароль не менялся.");
    } else {
        $uid = wp_insert_user([
            'user_login' => $u['email'],
            'user_email' => $u['email'],
            'user_pass' => $u['password'],
            'first_name' => $u['first'],
            'last_name' => $u['last'],
            'display_name' => trim($u['last'] . ' ' . $u['first']),
            'role' => 'subscriber',
        ]);
        if (is_wp_error($uid)) WP_CLI::error("Не удалось создать {$u['email']}: " . $uid->get_error_message());
        update_user_meta($uid, 'cininvest_phone', $u['phone']);
        update_user_meta($uid, 'cininvest_citizenship', 'РФ');
        WP_CLI::success("  Создан {$u['email']} (ID {$uid}).");
    }
    cininvest_set_user_role($uid, $role);
    $ids[$role] = $uid;
}

/* ---------------------------------------------------------------------
 * 2) Проект от кинематографиста
 * -------------------------------------------------------------------*/
WP_CLI::log('== Проект ==');

$project_slug = 'seed-demo-flip';
$project = get_page_by_path($project_slug, OBJECT, 'project');
if ($project) {
    $project_id = $project->ID;
    WP_CLI::log("  Проект '{$project_slug}' уже существует (ID {$project_id}), обновляю поля.");
} else {
    $project_id = wp_insert_post([
        'post_type' => 'project',
        'post_title' => 'Flip (демо-проект seed-demo.php)',
        'post_name' => $project_slug,
        'post_status' => 'publish',
        'post_author' => $ids['cinematographer'],
        'post_content' => 'Демонстрационный проект для сквозного теста платформы, создан tools/seed-demo.php.',
    ], true);
    if (is_wp_error($project_id)) WP_CLI::error('Не удалось создать проект: ' . $project_id->get_error_message());
    WP_CLI::success("  Создан проект ID {$project_id}.");

    // Базовые значения полей, которые дальше пересчитывает inc/ajax-shares.php при покупках
    // (collected/shares_available/investors_count/sponsors_count) — задаём их ТОЛЬКО при
    // создании проекта. Если переустанавливать их на каждом запуске сидера, повторный
    // запуск будет обнулять то, что уже накопили покупки инвестора/спонсора.
    update_field('collected', 850000, $project_id);
    update_field('shares_available', 120, $project_id);
    update_field('investors_count', 0, $project_id);
    update_field('sponsors_count', 0, $project_id);
}

update_field('goal', 3000000, $project_id);
update_field('share_price', 5000, $project_id);
update_field('min_package', '1 доля', $project_id);
update_field('commission', 4, $project_id);
update_field('org_name', 'ООО «Флип Продакшн»', $project_id);
update_field('project_status', 'active', $project_id);
update_field('genre', 'Драма, Триллер', $project_id);
update_field('duration', '104 мин', $project_id);
update_field('ptype', 'Полный метр', $project_id);
update_field('kind', 'Игровое', $project_id);
update_field('logline', 'Программист случайно взламывает архив закрытой киностудии и находит там сценарий, предсказавший его собственную жизнь.', $project_id);
update_field('idea', 'Технотриллер на стыке хакерской драмы и производственного кино о создании фильма.', $project_id);
update_field('setting', 'Москва, наши дни', $project_id);
update_field('annotation', 'Демо-аннотация проекта, сгенерированная сид-скриптом для сквозного теста.', $project_id);
update_field('synopsis', '<p>Демо-синопсис. Текст сгенерирован tools/seed-demo.php и предназначен только для проверки вёрстки/данных.</p>', $project_id);
update_field('locations', 'Москва, Санкт-Петербург', $project_id);

// 2 раунда — чтобы на странице проекта было на что переключаться (само переключение
// сейчас чисто визуальное: main.js меняет только активный таб, цифры карточки сбора
// не завязаны на конкретный раунд — это уже так во всём проекте, не баг сидера).
update_field('rounds', [
    ['name' => '1 раунд', 'active' => true],
    ['name' => '2 раунд', 'active' => false],
], $project_id);

update_field('team', [
    ['photo' => '', 'name' => 'Иван Съёмкин', 'role' => 'Режиссёр', 'about' => 'Дебютный полный метр, до этого — три короткометражки в фестивальной программе.', 'kinopoisk' => 'https://www.kinopoisk.ru/'],
    ['photo' => '', 'name' => 'Мария Кадрова', 'role' => 'Продюсер', 'about' => 'Продюсировала два сериала для стриминговых платформ.', 'kinopoisk' => 'https://www.kinopoisk.ru/'],
    ['photo' => '', 'name' => 'Олег Светов', 'role' => 'Оператор-постановщик', 'about' => 'Работал на рекламных проектах и одном полном метре.', 'kinopoisk' => 'https://www.kinopoisk.ru/'],
], $project_id);

$script_att = cininvest_seed_placeholder_attachment('material-script', 'seed-demo-script.txt', 'Сценарий (черновик)', "Демо-файл сценария.\nСоздан tools/seed-demo.php для сквозного теста.\n");
$deck_att = cininvest_seed_placeholder_attachment('material-deck', 'seed-demo-deck.txt', 'Презентация проекта', "Демо-презентация проекта.\nСоздана tools/seed-demo.php.\n");
update_field('materials', [
    ['title' => 'Сценарий (черновик)', 'file' => $script_att],
    ['title' => 'Презентация проекта', 'file' => $deck_att],
], $project_id);

$fin_att = cininvest_seed_placeholder_attachment('doc-financial', 'seed-demo-financial-model.txt', 'Финансовая модель', "Демо-финансовая модель.\nСоздана tools/seed-demo.php.\n");
$contract_att = cininvest_seed_placeholder_attachment('doc-contract', 'seed-demo-contract.txt', 'Договор соинвестирования (образец)', "Демо-договор.\nСоздан tools/seed-demo.php.\n");
update_field('docs', [
    ['title' => 'Финансовая модель', 'file' => $fin_att],
    ['title' => 'Договор соинвестирования (образец)', 'file' => $contract_att],
], $project_id);

update_field('rewards', [
    ['title' => 'Ранний доступ к трейлеру', 'price' => 300],
    ['title' => 'Упоминание в титрах', 'price' => 750],
    ['title' => 'Приглашение на закрытый показ', 'price' => 1500],
    ['title' => 'Мерч со съёмок (футболка + постер)', 'price' => 3000],
], $project_id);

update_field('reviews', [
    ['photo' => '', 'name' => 'Светлана Инвесторова', 'role' => 'Инвестор', 'text' => 'Отличная команда, прозрачная отчётность — вложился без колебаний.'],
    ['photo' => '', 'name' => 'Дмитрий Спонсоров', 'role' => 'Спонсор', 'text' => 'Поддержал проект, получил мерч раньше премьеры — очень доволен.'],
], $project_id);

WP_CLI::success('  Поля проекта заполнены.');

/* ---------------------------------------------------------------------
 * 3) Заявки от кинематографиста (CPT application) — 4 шт, по одной на каждый
 *    статус модерации, чтобы было видно и вкладку «Мои заявки», и очередь
 *    на модерацию во всех стадиях сразу.
 * -------------------------------------------------------------------*/
WP_CLI::log('== Заявки кинематографиста ==');

$app_common = [
    'genre' => 'Драма', 'duration' => '96 мин', 'ptype' => 'Полный метр', 'kind' => 'Игровое',
    'logline' => 'Молодой оператор возвращается в родной город, чтобы снять фильм о людях, которых когда-то предал.',
    'idea' => 'Драма о цене возвращения и цене прощения, снятая в духе медленного кино.',
    'setting' => 'Приволжский городок, наши дни',
    'annotation' => 'Демо-аннотация заявки, сгенерирована tools/seed-demo.php.',
    'synopsis' => 'Демо-синопсис заявки для сквозного теста платформы.',
    'locations' => 'Кострома, Ярославль',
    'company' => 'ИП/Продакшн-группа (демо)',
    'head_fio' => 'Руководителев Руководитель Руководителевич',
    'producer_fio' => 'Продюсерова Полина', 'producer_kp' => 'https://www.kinopoisk.ru/',
    'director_fio' => 'Съёмкин Иван', 'director_kp' => 'https://www.kinopoisk.ru/',
    'writer_fio' => 'Сценаристов Сергей', 'writer_kp' => 'https://www.kinopoisk.ru/',
    'operator_fio' => 'Кадров Кирилл', 'operator_kp' => 'https://www.kinopoisk.ru/',
    'artist_fio' => 'Художникова Ада', 'artist_kp' => '',
    'composer_fio' => 'Композиторов Клим', 'composer_kp' => '',
    'total_duration' => '8',
    'budget' => '5 000 000', 'own_funds' => '500 000', 'requested' => '4 500 000', 'spent' => '150 000',
];
$app_periods = [
    ['name' => 'Подготовительный период', 'active' => true, 'start' => '01.03.2026', 'end' => '30.04.2026'],
    ['name' => 'Съемочный/производственный период', 'active' => false, 'start' => '01.05.2026', 'end' => '31.07.2026'],
    ['name' => 'Монтажно-тонировочный период', 'active' => false, 'start' => '01.08.2026', 'end' => '30.09.2026'],
    ['name' => 'Прокат', 'active' => false, 'start' => '01.10.2026', 'end' => ''],
];
$app_rewards = ['Ранний доступ к стримингу (онлайн просмотр)', 'Приглашение на специальный показ'];

$app_fiz_fields = [
    'education' => 'Высшее', 'additional' => 'Демо-заявка, физлицо.',
    'applicant_phone' => '+7 900 111-11-11',
    'inn' => '773456789012', 'snils' => '123-456-789 00',
    'bank_name' => 'ПАО Сбербанк', 'bik' => '044525225',
    'corr_account' => '30101810400000000225', 'settle_account' => '40817810099910004312',
];
$app_fiz_arrays = [
    'edu_org' => ['ВГИК, режиссура игрового кино'],
    'experience' => ['3 короткометражки, участник фестивалей'],
    'social' => ['https://vk.com/example'],
];

$app_ur_fields = [
    'org_name' => 'ООО «Кинокомпания Пример»',
    'ceo_last' => 'Директорова', 'ceo_first' => 'Дарья', 'ceo_middle' => 'Игоревна',
    'position' => 'Генеральный директор', 'applicant_phone' => '+7 900 222-22-22',
    'legal_address' => 'г. Москва, ул. Примерная, д. 1',
    'ogrn' => '1157746000000', 'inn' => '7712345678', 'kpp' => '771201001',
    'ogrn_date' => '15.03.2015', 'okved' => '59.11',
    'bik' => '044525225', 'bank_name' => 'ПАО Сбербанк',
    'corr_account' => '30101810400000000225', 'settle_account' => '40702810099910004312',
];

$applications = [
    ['key' => 'app-draft', 'status' => 'draft', 'type' => 'fiz', 'title' => 'Черновик: Северный ветер'],
    ['key' => 'app-submitted', 'status' => 'submitted', 'type' => 'fiz', 'title' => 'Северный ветер'],
    ['key' => 'app-pre-review', 'status' => 'pre_review', 'type' => 'ur', 'title' => 'Точка невозврата'],
    ['key' => 'app-approved', 'status' => 'approved', 'type' => 'ur', 'title' => 'Остров тишины'],
];

foreach ($applications as $a) {
    $fields = $app_common;
    $array_fields = [];
    if ($a['type'] === 'fiz') {
        $fields = array_merge($fields, $app_fiz_fields);
        $array_fields = array_merge($array_fields, $app_fiz_arrays);
    } else {
        $fields = array_merge($fields, $app_ur_fields);
    }
    $array_fields['periods'] = $app_periods;
    $array_fields['rewards'] = $app_rewards;

    $app_id = cininvest_seed_ensure_application(
        $a['key'], $ids['cinematographer'], $a['type'], $a['status'], $a['title'], $fields, $array_fields
    );
    if ($app_id) {
        WP_CLI::success("  [{$a['status']}] «{$a['title']}» (ID {$app_id}, {$a['type']}).");
    }
}

/* ---------------------------------------------------------------------
 * 4) Инвестор: депозит + покупка долей (через тот же charge(), что и ajax-shares.php)
 * -------------------------------------------------------------------*/
WP_CLI::log('== Инвестор: пополнение и покупка долей ==');
cininvest_seed_ensure_deposit($ids['investor'], 200000, 'Инвестор');

$existing_buy = cininvest_seed_find_operation($ids['investor'], 'buy', $project_id);
if ($existing_buy) {
    WP_CLI::log("  Доли уже куплены ранее (операция #{$existing_buy->id}) — пропускаю.");
} else {
    $shares = 10;
    $share_price = (float) get_field('share_price', $project_id);
    $commission_pct = (float) get_field('commission', $project_id);
    $base = $shares * $share_price;
    $commission = round($base * $commission_pct / 100, 2);
    $total = $base + $commission;

    // ровно тот же вызов, что inc/ajax-shares.php::cininvest_handle_buy_shares()
    $result = cininvest_gateway()->charge($ids['investor'], $total, [
        'op_type' => 'buy', 'shares' => $shares, 'project_id' => $project_id,
        'commission' => $commission,
        'share_price' => $share_price, 'base' => $base,
        'seed' => CININVEST_SEED_TAG,
    ]);
    if (is_wp_error($result)) WP_CLI::error('Инвестор: покупка долей не удалась — ' . $result->get_error_message());
    WP_CLI::success("  Куплено {$shares} долей за " . cininvest_money($total) . " (операция #{$result}).");

    // тот же пересчёт витринных полей, что теперь делает cininvest_handle_buy_shares()
    update_field('collected', (float) get_field('collected', $project_id) + $base, $project_id);
    update_field('investors_count', (int) get_field('investors_count', $project_id) + 1, $project_id);
    update_field('shares_available', max(0, (int) get_field('shares_available', $project_id) - $shares), $project_id);
    WP_CLI::log('  Пересчитаны collected/investors_count/shares_available проекта.');
}

/* ---------------------------------------------------------------------
 * 5) Спонсор: депозит + покупка вознаграждений (через тот же charge(), что и ajax-shares.php)
 * -------------------------------------------------------------------*/
WP_CLI::log('== Спонсор: пополнение и покупка вознаграждений ==');
cininvest_seed_ensure_deposit($ids['sponsor'], 20000, 'Спонсор');

$existing_perks = cininvest_seed_find_operation($ids['sponsor'], 'privileges', $project_id);
if ($existing_perks) {
    WP_CLI::log("  Вознаграждения уже куплены ранее (операция #{$existing_perks->id}) — пропускаю.");
} else {
    $all_rewards = cininvest_project_rewards($project_id);
    $chosen = array_slice($all_rewards, 1, 2); // 2 вознаграждения из набора проекта
    $total = array_sum(wp_list_pluck($chosen, 'price'));

    // ровно тот же вызов, что inc/ajax-shares.php::cininvest_handle_buy_perks()
    $result = cininvest_gateway()->charge($ids['sponsor'], $total, [
        'op_type' => 'privileges', 'project_id' => $project_id,
        'items' => $chosen,
        'seed' => CININVEST_SEED_TAG,
    ]);
    if (is_wp_error($result)) WP_CLI::error('Спонсор: покупка вознаграждений не удалась — ' . $result->get_error_message());
    WP_CLI::success('  Куплено: ' . implode(', ', wp_list_pluck($chosen, 'title')) . ' за ' . cininvest_money($total) . " (операция #{$result}).");

    // тот же пересчёт витринного поля, что теперь делает cininvest_handle_buy_perks()
    update_field('sponsors_count', (int) get_field('sponsors_count', $project_id) + 1, $project_id);
    WP_CLI::log('  Пересчитан sponsors_count проекта.');
}

/* ---------------------------------------------------------------------
 * 6) Проверка: операции, балансы, заявки
 * -------------------------------------------------------------------*/
WP_CLI::log('== Проверка ==');
$apps_count = (new WP_Query(['post_type' => 'application', 'author' => $ids['cinematographer'], 'posts_per_page' => -1, 'fields' => 'ids']))->found_posts;
WP_CLI::log("  Заявок кинематографиста в базе: {$apps_count}");
foreach (['cinematographer', 'investor', 'sponsor'] as $role) {
    $uid = $ids[$role];
    $ops = cininvest_get_operations($uid, 20);
    WP_CLI::log("  {$role} ({$users[$role]['email']}), баланс " . cininvest_money(cininvest_get_balance($uid)) . ', операций: ' . count($ops));
    foreach ($ops as $op) {
        WP_CLI::log("    #{$op->id}  {$op->type}  {$op->status}  " . cininvest_money($op->amount));
    }
}

/* ---------------------------------------------------------------------
 * Итог
 * -------------------------------------------------------------------*/
WP_CLI::log('');
WP_CLI::log('========================================================');
WP_CLI::log('ИТОГ');
WP_CLI::log('========================================================');
foreach ($users as $role => $u) {
    $account_url = cininvest_account_url_for_role($role);
    WP_CLI::log(sprintf('%-16s %-22s / %-20s %s', ucfirst($role), $u['email'], $u['password'], $account_url));
}
WP_CLI::log('');
WP_CLI::log('Проект: ID ' . $project_id . '  ' . get_permalink($project_id));
WP_CLI::log('');
WP_CLI::log('Заявки кинематографиста (вкладка "Мои заявки" в ' . cininvest_account_url_for_role('cinematographer') . '):');
foreach ($applications as $a) {
    WP_CLI::log("  [{$a['status']}] {$a['title']}");
}
WP_CLI::log('');
WP_CLI::log('Проверьте на купленных этим скриптом долях/вознаграждениях:');
WP_CLI::log(' - модалка операции инвестора должна показывать "Стоимость одной доли" (было пусто —');
WP_CLI::log('   исправлено: meta.share_price теперь плоско, на одном уровне с остальными ключами);');
WP_CLI::log(' - на странице проекта goal/collected должны отражать покупку инвестора, а');
WP_CLI::log('   investors_count/sponsors_count/shares_available — покупки инвестора и спонсора');
WP_CLI::log('   (было: не пересчитывались вообще — исправлено в inc/ajax-shares.php);');
WP_CLI::log(' - таксономия project_status в inc/cpt.php была неиспользуемым дублем ACF-поля с тем же');
WP_CLI::log('   именем — удалена, витрина работает через ACF-поле, как и раньше.');
WP_CLI::log('========================================================');
