<?php 

// Области меню
function chininvest_menus() {
    $locations = array(
        'header-menu' => __('Heder Menu', 'chininvest_theme'),
    );

    register_nav_menus ( $locations );
}

add_action( 'init', 'chininvest_menus' );
?>

<?php 
//добавление кнопки "войти/личный кабинет"
add_filter( 'wp_nav_menu_items', 'add_custom_menu_item', 10, 2 );
function add_custom_menu_item( $items, $args ) {
    if ( $args->theme_location == 'header-menu' ) {
        if ( is_user_logged_in() ) {
            //ссылка на личный кабинет
            $new_item = '<button class="menu-item custom-class">';
            $new_item .= '<a href="' . esc_url( home_url( '/?page_id=64/' ) ) . '">Личный кабинет</a>';
            $new_item .= '</button>';
        }
        else {
            $new_item = '<button class="menu-item custom-class">';
            $new_item .= '<a href="' . esc_url( home_url( '/wp-login.php/' ) ) . '">Вход</a>';
            $new_item .= '</button>';

            $new_item .= '<button class="menu-item custom-class">';
            $new_item .= '<a href="' . esc_url( home_url( '/wp-login.php?action=register' ) ) . '">Регистрация</a>';
            $new_item .= '</button>';
        }

        // Добавляем в конец меню
        $items = $items . $new_item;

    }
    return $items;
}
?>

<?php
// Добавление ролей пользователя

// Удаление ролей при деактивации темы
add_action( 'switch_theme', 'deactivate_my_theme' );
function deactivate_my_theme() {
	remove_role( 'cinematographer' );
    remove_role( 'investor' );
}

// Добавление ролей при активации темы
add_action( 'after_switch_theme', 'activate_my_theme' );
function activate_my_theme() {
	add_role( 'cinematographer', 'Кинематографист',
		[
            //возможность роли:
			'read'         => true,
		]
	);
    add_role( 'investor', 'Инвестор',
		[
			'read'         => true,
		]
	);
    add_role( 'sponsor', 'Спонсор',
		[
			'read'         => true,
		]
	);
}


//дополнительные поля для регистрации + выбор роли
add_action('register_form', 'add_role_field_to_registration');

function add_role_field_to_registration(){
    // получаем значения из POST, если форма была отправлена с ошибкой
    $first_name = ( ! empty( $_POST['first_name'] ) ) ? sanitize_text_field( $_POST['first_name'] ) : '';
    $last_name  = ( ! empty( $_POST['last_name'] ) ) ? sanitize_text_field( $_POST['last_name'] ) : '';
    $patronymic = ( ! empty( $_POST['patronymic'] ) ) ? sanitize_text_field( $_POST['patronymic'] ) : '';
    $phone = ( ! empty( $_POST['phone'] ) ) ? sanitize_text_field( $_POST['phone'] ) : '';
    $citizenship = ( ! empty( $_POST['citizenship'] ) ) ? sanitize_text_field( $_POST['citizenship'] ) : '';

    $roles = array(
        'cinematographer' => 'Кинематографист',
        'sponsor' => 'Инвестор',
    );
    ?>
    <p>
    <!-- выпадающий список с ролями -->
    <select name="user_role">'
        <?php foreach ($roles as $role_key => $role_name): ?>
                <option value="<?php echo esc_attr($role_key); ?>">
                    <?php echo esc_html(translate_user_role($role_name)); ?>
                </option>
        <?php endforeach; ?>
    </select>
    </p>
    <p>
        <label for="phone">Мобильный телефон *<br />
            <input type="tel" id="phone" name="phone" class="input" 
                   value="<?php echo ( esc_attr($phone) ); ?>"
                   size="25" pattern="\+?[0-9\s\-\(\)]+" 
                   placeholder="Введите корректный номер мобильного телефона" />
        </label>
    </p>
    <p>
        <label for="last_name">Фамилия</label>
        <input type="text" id="last_name" name="last_name" class="input" value="<?php echo ( esc_attr($last_name) ); ?>" size="25" />
    </p>
    <p>
        <label for="first_name">Имя</label>
        <input type="text" id="first_name" name="first_name" class="input" value="<?php echo ( esc_attr($first_name) ); ?>" size="25" />
    </p>
    <p>
        <label for="patronymic">Отчество</label>
        <input type="text" id="patronymic" name="patronymic" class="input" value="<?php echo ( esc_attr($patronymic) ); ?>" size="25" />
    </p>
    <p>
        <label for="citizenship">Гражданство</label>
        <input type="text" id="citizenship" name="citizenship" class="input" value="<?php echo ( esc_attr($citizenship) ); ?>" size="25" />
    </p>
    <p>
        <label for="password">Пароль</label>
        <input type="password" id="password" name="password" class="input" value="" size="25" />
    </p>
    <p>
        <label for="confirm_password">Повторите пароль</label>
        <input type="password" id="confirm_password" name="confirm_password" class="input" value="" size="25" />
    </p>
    <p>
        <label for="accept_privacy_policy">
            <!-- checked() - выводит html атрибут checked="checked" -->
            <input type="checkbox" id="accept_privacy_policy" name="accept_privacy_policy" value="1" <?php checked(!empty($_POST['accept_privacy_policy'])); ?> />
            Даю согласие на обработку персональных данных
            </a> *
        </label>
    </p>

<?php
}

add_filter('registration_errors', 'validate_fields', 10, 3);
function validate_fields( $errors, $sanitized_user_login, $user_email ) {
    // Проверка обязательных текстовых полей
    if ( empty( $_POST['first_name'] ) || trim( $_POST['first_name'] ) == '' ) {
        $errors->add( 'first_name_error', '<strong>Ошибка:</strong> Пожалуйста, укажите ваше Имя.' );
    }
    if ( empty( $_POST['last_name'] ) || trim( $_POST['last_name'] ) == '' ) {
        $errors->add( 'last_name_error', '<strong>Ошибка:</strong> Пожалуйста, укажите вашу Фамилию.' );
    } 
    if ( empty( $_POST['patronymic'] ) || trim( $_POST['patronymic'] ) == '' ) {
        $errors->add( 'patronymic_error', '<strong>Ошибка:</strong> Пожалуйста, укажите ваше Отчество.' );
    } 
    if ( empty( $_POST['citizenship'] ) || trim( $_POST['citizenship'] ) == '' ) {
        $errors->add( 'citizenship_error', '<strong>Ошибка:</strong> Пожалуйста, заполните поле Гражданство.' );
    } 
    if (empty($_POST['phone']) || trim( $_POST['first_name'] ) == '') {
        $errors->add('phone_empty', '<strong>Ошибка:</strong> Укажите номер телефона.');
    }

    // Проверка паролей
    if ( empty( $_POST['password'] ) ) {
        $errors->add( 'empty_password', '<strong>Ошибка:</strong> Введите пароль.' );
    } elseif ( $_POST['password'] !== $_POST['confirm_password'] ) {
        $errors->add( 'password_mismatch', '<strong>Ошибка:</strong> Пароли не совпадают.' );
    }

    // Проверка чекбокса
    // isset проверяет не равна ли переменная null
    if (!isset($_POST['accept_privacy_policy']) || $_POST['accept_privacy_policy'] != '1') {
        $errors->add(
            'privacy_policy_error', 
            '<strong>Ошибка:</strong> Вы должны согласиться с политикой обработки персональных данных для завершения регистрации.'
        );
    }
    return $errors;
}

// сохранение заполненных полей
add_action('user_register', 'assign_fields');
function assign_fields($user_id) {
    if ( ! empty( $_POST['first_name'] ) ) {
        update_user_meta( $user_id, 'first_name', sanitize_text_field( $_POST['first_name'] ) );
    }
    if ( ! empty( $_POST['last_name'] ) ) {
        update_user_meta( $user_id, 'last_name', sanitize_text_field( $_POST['last_name'] ) );
    }
    if ( ! empty( $_POST['patronymic'] ) ) {
        update_user_meta( $user_id, 'patronymic', sanitize_text_field( $_POST['patronymic'] ) );
    }
    if ( ! empty( $_POST['citizenship'] ) ) {
        update_user_meta( $user_id, 'citizenship', sanitize_text_field( $_POST['citizenship'] ) );
    }

    if ( ! empty($_POST['phone'] ) ) {
        $phone = sanitize_text_field($_POST['phone']);
        update_user_meta($user_id, 'phone', $phone);
    }

    if ( ! empty( $_POST['password'] ) ) {
        wp_set_password( $_POST['password'], $user_id );
    }
    if(isset($_POST['user_role']) && !empty($_POST['user_role'])) {
        $role = sanitize_text_field($_POST['user_role']);
        $user = new WP_User($user_id);
        $user->set_role($role);
    }
}


//показать дополнительные поля в админке
add_action( 'show_user_profile', 'my_show_extra_profile_fields' );
add_action( 'edit_user_profile', 'my_show_extra_profile_fields' );
function my_show_extra_profile_fields( $user ) {
    ?>
    <table class="form-table">
        <tr>
            <th><label for="patronymic">Отчество</label></th>
            <td>
                <input type="text" name="patronymic" id="patronymic" value="<?php echo esc_attr( get_the_author_meta( 'patronymic', $user->ID ) ); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="phone">Номер телефона</label></th>
            <td>
                <input type="text" name="phone" id="phone" value="<?php echo esc_attr( get_the_author_meta( 'phone', $user->ID ) ); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="citizenship">Гражданство</label></th>
            <td>
                <input type="text" name="citizenship" id="citizenship" value="<?php echo esc_attr( get_the_author_meta( 'citizenship', $user->ID ) ); ?>" class="regular-text" />
            </td>
        </tr>
    </table>

    <?php   
    //дополнительные поля, которые добавляются взависимости от роли
    if ( in_array( 'cinematographer', (array) $user->roles ) ) {
        $educations = array();

        $current_education_key = $user->education;

        if ($current_education_key == 'professional'){
            $educations['professional'] = 'Профессиональное';
            $educations['higher'] = 'Высшее';
        }
        else if ($current_education_key == 'higher'){
            $educations['higher'] = 'Высшее';
            $educations['professional'] = 'Профессиональное';
        }
        else{
            $educations['professional'] = 'Профессиональное';
            $educations['higher'] = 'Высшее';
        }

        ?>
        <p>
        <label for="education">Выберите вид образования</label>
        <select name="education" id = "education">
            <?php foreach ($educations as $education_key => $education): ?>
                    <option value="<?php echo esc_attr($education_key); ?>">
                        <?php echo esc_html($education); ?>
                        <!-- translate_user_role() для корректной локализации -->
                    </option>
            <?php endforeach; ?>
        </select>
        </p>
        
        <?php 
            // если существует обьект user и у него есть поле id
            $saved_fields = $user && isset($user->ID) ? get_user_meta($user->ID, 'custom_text_fields', true) : array(); 

            $fields = is_array($saved_fields) ? $saved_fields : array('');
        ?>
        <h3>Образовательные организации и специальности</h3>
        <?php foreach ($fields as $index => $value): ?>
            <div class="field-row">
                <input type="text"
                style="width: 80%;"
                value="<?php echo esc_attr($value); ?>" 
                name="custom_text_fields[]"
                placeholder="Образовательные организации и специальности">

                <?php if ($index === 0) : ?>
                    <!-- у первой строки кнопка "Добавить" -->
                    <button type="button" class="button add-field" style="margin-left: 10px;">+ Добавить</button>
                <?php else : ?>
                    <!-- у остальных строк кнопка "Удалить" -->
                    <button type="button" class="button remove-field" style="margin-left: 10px;">– Удалить</button>
                <?php endif; ?>
            </div>
        <?php endforeach ?>

        <?php
            $work_experience_fields = $user && isset($user->ID) ? get_user_meta($user->ID, 'work_experience_fields', true) : array(); 

            $work_fields = is_array($work_experience_fields) ? $work_experience_fields : array('');

            if (empty($work_fields)) {
                $work_fields = array('');
            }
            ?>

            <h3>Опыт работы</h3>
            <?php foreach ($work_fields as $index => $value): ?>     
            <div class="field-row">
                <input type="text"
                style="width: 80%;"
                value="<?php echo esc_attr($value); ?>" 
                name="work_experience_fields[]"
                placeholder="Опыт работы">

                <?php if ($index === 0) : ?>
                    <!-- у первой строки кнопка "Добавить" -->
                    <button type="button" class="button add-field" style="margin-left: 10px;">+ Добавить</button>
                <?php else : ?>
                    <!-- у остальных строк кнопка "Удалить" -->
                    <button type="button" class="button remove-field" style="margin-left: 10px;">– Удалить</button>
                <?php endif; ?>
            </div>
            <?php endforeach ?> 

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            //кнопка добавления поля
            $(document).on('click', '.add-field', function() {
                var row = $(this).closest('.field-row').clone();
                row.find('input').val('');
                row.find('.add-field').removeClass('add-field').addClass('remove-field').text('– Удалить');
                row.insertAfter($(this).closest('.field-row'));
            });

            // кнопка удаления поля
            $(document).on('click', '.remove-field', function() {
                if ($('.field-row').length > 1) {
                    $(this).closest('.field-row').remove();
                }
            });
        });
        </script>

        <p>
            <label for="kinopoisk_link">Я на Кинопоиске</label>
            <input type="url" id="kinopoisk_link" name="kinopoisk_link" class="input" value="<?php echo esc_attr( get_the_author_meta( 'kinopoisk_link', $user->ID ) ); ?>" size="25" />
        </p>
    <?php
    }
}

// обновить поля при редактировании в админке
add_action( 'personal_options_update', 'my_save_extra_profile_fields' );
add_action( 'edit_user_profile_update', 'my_save_extra_profile_fields' );
function my_save_extra_profile_fields( $user_id ) {
    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        return false;
    }
    if ( isset( $_POST['patronymic'] ) ) {
        update_user_meta( $user_id, 'patronymic', sanitize_text_field( $_POST['patronymic'] ) );
    }
    if ( isset( $_POST['phone'] ) ) {
        update_user_meta( $user_id, 'phone', sanitize_text_field( $_POST['phone'] ) );
    }
    if ( isset( $_POST['citizenship'] ) ) {
        update_user_meta( $user_id, 'citizenship', sanitize_text_field( $_POST['citizenship'] ) );
    }

    //сохранение полей для разных ролей
    if ( isset( $_POST['education'] ) ) {
        update_user_meta( $user_id, 'education', sanitize_text_field( $_POST['education'] ) );
    }

    if ( isset( $_POST['custom_text_fields'] ) && is_array( $_POST['custom_text_fields'] ) ) {
        // Очищаем массив: удаляем пустые строки и обрезаем пробелы
        $sanitized_fields = array();
        foreach ( $_POST['custom_text_fields'] as $field ) {
            $trimmed = trim( sanitize_text_field( $field ) );
            if ( ! empty( $trimmed ) ) {
                $sanitized_fields[] = $trimmed;
            }
        }
        // Сохраняем массив в метаполе пользователя
        update_user_meta( $user_id, 'custom_text_fields', $sanitized_fields );
    }

    if ( isset( $_POST['work_experience_fields'] ) && is_array( $_POST['work_experience_fields'] ) ) {
        $sanitized_fields = array();
        foreach ( $_POST['work_experience_fields'] as $field ) {
            $trimmed = trim( sanitize_text_field( $field ) );
            if ( ! empty( $trimmed ) ) {
                $sanitized_fields[] = $trimmed;
            }
        }
        // Сохраняем массив в метаполе пользователя
        update_user_meta( $user_id, 'work_experience_fields', $sanitized_fields );
    }

    if ( isset( $_POST['kinopoisk_link'] ) ) {
        update_user_meta( $user_id, 'kinopoisk_link', sanitize_text_field( $_POST['kinopoisk_link'] ) );
    }
}

//шорткод для вывода формы редактирования профиля.
//[edit_profile_form]
add_shortcode( 'edit_profile_form', 'edit_profile_form' );

function edit_profile_form(){
    if ( ! is_user_logged_in() ) {
        return '<p>Для редактирования профиля необходимо <a href="' . wp_login_url( get_permalink() ) . '">войти</a>.</p>';
    }
    $message = '';

    $current_user = wp_get_current_user(); // получаем объект текущего пользователя
    $user_id = $current_user->ID;
    if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'frontend_update_profile_' . $user_id ) )  
    {
        if ( isset( $_POST['first_name'] ) ) {
            wp_update_user( array( 'ID' => $user_id, 'first_name' => sanitize_text_field( $_POST['first_name'] ) ) );
        }

        if ( isset( $_POST['last_name'] ) ) {
            wp_update_user( array( 'ID' => $user_id, 'last_name' => sanitize_text_field( $_POST['last_name'] ) ) );
        }

        if ( isset( $_POST['patronymic'] ) ) {
            update_user_meta( $user_id, 'patronymic', sanitize_text_field( $_POST['patronymic'] ) );
        }

        if ( isset( $_POST['user_email'] ) ) {
            $email = sanitize_email( $_POST['user_email'] );
            if ( ! is_email( $email ) ) {
                $message .= '<p class="error">Ошибка: Введен некорректный email.</p>';
            } else {
                wp_update_user( array( 'ID' => $user_id, 'user_email' => $email ) );
            }
        }

        if ( isset( $_POST['phone'] ) ) {
            update_user_meta( $user_id, 'phone', sanitize_text_field( $_POST['phone'] ) );
        }

        if ( in_array( 'cinematographer', (array) $current_user->roles ) ) {
            if ( isset( $_POST['education'] ) ) {
            update_user_meta( $user_id, 'education', sanitize_text_field( $_POST['education'] ) );
            }

            if ( isset( $_POST['custom_text_fields'] ) && is_array( $_POST['custom_text_fields'] ) ) {
            $sanitized_fields = array();
            foreach ( $_POST['custom_text_fields'] as $field ) {
                $trimmed = trim( sanitize_text_field( $field ) );
                if ( ! empty( $trimmed ) ) {
                    $sanitized_fields[] = $trimmed;
                }
            }
            update_user_meta( $user_id, 'custom_text_fields', $sanitized_fields);   
            }

            if ( isset( $_POST['work_experience_fields'] ) && is_array( $_POST['work_experience_fields'] ) ) {
            $sanitized_fields = array();
            foreach ( $_POST['work_experience_fields'] as $field ) {
                $trimmed = trim( sanitize_text_field( $field ) );
                if ( ! empty( $trimmed ) ) {
                    $sanitized_fields[] = $trimmed;
                }
            }

            update_user_meta( $user_id, 'work_experience_fields', $sanitized_fields);   
            }
        }
        echo $message;
    }

        //вывод формы:
        ob_start();
        ?>
        <div class="frontend-profile-form">
        <form method="post" action="">
            <!-- nonce-проверка для безопасности -->
            <?php wp_nonce_field( 'frontend_update_profile_' . $user_id, '_wpnonce' ); ?>

            <h3>Личные данные</h3>

            <p>
                <label for="first_name">Имя:</label><br>
                <input type="text" name="first_name" id="first_name" value="<?php echo esc_attr( $current_user->first_name ); ?>">
            </p>

            <p>
                <label for="last_name">Фамилия:</label><br>
                <input type="text" name="last_name" id="last_name" value="<?php echo esc_attr( $current_user->last_name ); ?>">
            </p>

            <p>
                <label for="patronymic">Отчество</label><br>
                <input type="text" name="patronymic" id="patronymic" value="<?php echo esc_attr( $current_user->patronymic ); ?>">
            </p>


            <p>
                <label for="user_email">Email</label><br>
                <input type="email" name="user_email" id="user_email" value="<?php echo esc_attr( $current_user->user_email ); ?>">
            </p>

            <p>
                <label for="phone">Номер телефона</label><br>
                <input type="tel" name="phone" id="phone" value="<?php echo esc_attr( get_user_meta( $user_id, 'phone', true ) ); ?>">
            </p>

            <?php

            //вывод в зависимости от роли
            if ( in_array( 'cinematographer', (array) $current_user->roles ) ) {
                $educations = array();
                $current_education_key = $current_user->education;

                if ($current_education_key == 'professional'){
                    $educations['professional'] = 'Профессиональное';
                    $educations['higher'] = 'Высшее';
                }
                else if ($current_education_key == 'higher'){
                    $educations['higher'] = 'Высшее';
                    $educations['professional'] = 'Профессиональное';
                }
                else{
                    $educations['professional'] = 'Профессиональное';
                    $educations['higher'] = 'Высшее';
                }
                ?>

                <p>
                    <label for="education">Выберите вид образования</label>
                    <select name="education" id = "education">'
                        <?php foreach ($educations as $education_key => $education): ?>
                                <option value="<?php echo esc_attr($education_key); ?>">
                                    <?php echo esc_html(translate_user_role($education)); ?>
                                </option>
                        <?php endforeach; ?>
                    </select>
                </p>

                <?php
                $saved_fields = $user_id && isset($user_id) ? get_user_meta($user_id, 'custom_text_fields', true) : array(); 

                $fields = is_array($saved_fields) ? $saved_fields : array('');
                ?>
                <h3>Образовательные организации и специальности</h3>
                <?php foreach ($fields as $index => $value): ?>
                    <div class="field-row">
                        <input type="text"
                        style="width: 80%;"
                        value="<?php echo esc_attr($value); ?>" 
                        name="custom_text_fields[]"
                        placeholder="Образовательные организации и специальности">

                        <?php if ($index === 0) : ?>
                            <!-- у первой строки кнопка "Добавить" -->
                            <button type="button" class="button add-field" style="margin-left: 10px;">Добавить еще</button>
                        <?php else : ?>
                            <!-- у остальных строк кнопка "Удалить" -->
                            <button type="button" class="button remove-field" style="margin-left: 10px;">Удалить</button>
                        <?php endif; ?>
                    </div>
                <?php endforeach ?>

                <?php
                $work_experience_fields = $user_id && isset($user_id) ? get_user_meta($user_id, 'work_experience_fields', true) : array(); 

                $work_fields = is_array($work_experience_fields) ? $work_experience_fields : array('');

                if (empty($work_fields)) {
                    $work_fields = array('');
                }
                ?>

                <h3>Опыт работы</h3>
                <?php foreach ($work_fields as $index => $value): ?>     
                <div class="field-row">
                    <input type="text"
                    style="width: 80%;"
                    value="<?php echo esc_attr($value); ?>" 
                    name="work_experience_fields[]"
                    placeholder="Опыт работы">

                    <?php if ($index === 0) : ?>
                        <!-- у первой строки кнопка "Добавить" -->
                        <button type="button" class="button add-field" style="margin-left: 10px;">Добавить еще</button>
                    <?php else : ?>
                        <!-- у остальных строк кнопка "Удалить" -->
                        <button type="button" class="button remove-field" style="margin-left: 10px;">Удалить</button>
                    <?php endif; ?>
                </div>
                <?php endforeach ?>
            <?php
            }
            ?> 

            <script type="text/javascript">
            // проверка, что страница загружена
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initForm);
            } else {
                initForm();
            }
            
            function initForm() {
                document.querySelector('.frontend-profile-form').addEventListener('click', function(e) {
                    //добавление поля
                    if (e.target.classList.contains('add-field')) {
                        e.preventDefault();
                        var row = e.target.closest('.field-row');
                        var newRow = row.cloneNode(true);
                        newRow.querySelector('input').value = '';

                        var btn = newRow.querySelector('button');
                        btn.classList.remove('add-field');
                        btn.classList.add('remove-field');
                        btn.textContent = 'Удалить';
                        
                        row.after(newRow);
                    }

                    //удаление поля
                    if (e.target.classList.contains('remove-field')) {
                        e.preventDefault();
                        
                        var row = e.target.closest('.field-row');
                        var container = row.parentElement;
                        
                        if (container.querySelectorAll('.field-row').length > 1) {
                            row.remove();
                        }
                    }
                })
            }
            
            </script>

            <p>
                <input type="submit" value="Сохранить изменения">
            </p>
        </form>
    </div>

    <?php
    return ob_get_clean();
    
}

?>