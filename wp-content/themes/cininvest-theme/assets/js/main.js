document.addEventListener('DOMContentLoaded', () => {

  /* Защита от двойной отправки: блокировка submit-кнопки/AJAX-кнопки после первого клика.
     Текст меняется на «Отправка…», разблокировка — через 30 сек (на случай, если страница
     не перешла дальше — например, серверная валидация вернула на ту же форму). */
  const DOUBLE_SUBMIT_LOCK_MS = 30000;
  function lockControl(el) {
    if (!el || el.disabled) return;
    const isValueEl = el.tagName === 'INPUT';
    if (el.dataset.origLabel === undefined) el.dataset.origLabel = isValueEl ? el.value : el.innerHTML;
    el.disabled = true;
    el.classList.add('is-submitting');
    if (isValueEl) el.value = 'Отправка…'; else el.textContent = 'Отправка…';
    setTimeout(() => {
      el.disabled = false;
      el.classList.remove('is-submitting');
      if (el.dataset.origLabel !== undefined) {
        if (isValueEl) el.value = el.dataset.origLabel; else el.innerHTML = el.dataset.origLabel;
      }
    }, DOUBLE_SUBMIT_LOCK_MS);
  }

  // делегированный обработчик — работает для всех форм на сайте, включая появившиеся в модалках
  document.addEventListener('submit', e => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.dataset.submitting === '1') { e.preventDefault(); return; }
    form.dataset.submitting = '1';
    // блокируем кнопки чуть позже текущего такта, чтобы браузер успел прочитать
    // name/value отправившей кнопки (submitter) при построении данных формы
    setTimeout(() => {
      form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(lockControl);
    }, 0);
    setTimeout(() => { form.dataset.submitting = '0'; }, DOUBLE_SUBMIT_LOCK_MS);
  }, true);

  /* Бургер-меню */
  const burger = document.querySelector('.burger');
  const nav = document.getElementById('main-nav');
  if (burger && nav) {
    burger.addEventListener('click', () => {
      const open = nav.classList.toggle('is-open');
      burger.setAttribute('aria-expanded', open);
      document.body.style.overflow = open ? 'hidden' : '';
    });
  }

  /* Универсальные табы ([data-tabs] используется в профилях и на странице проекта) */
  document.querySelectorAll('[data-tabs]').forEach(tabs => {
    const links = tabs.querySelectorAll('.tabs__link');
    const panels = tabs.querySelectorAll('.tabs__panel');
    const activate = link => {
      links.forEach(l => l.classList.remove('tabs__link--active'));
      panels.forEach(p => p.classList.remove('is-active'));
      link.classList.add('tabs__link--active');
      tabs.querySelector(`[data-panel="${link.dataset.tab}"]`)?.classList.add('is-active');
    };
    links.forEach(link => link.addEventListener('click', () => {
      activate(link);
      // сохраняем активную вкладку в #hash, чтобы она осталась после перезагрузки
      if (link.dataset.tab) history.replaceState(null, '', '#' + link.dataset.tab);
    }));
    // при загрузке восстанавливаем вкладку из #hash (в т.ч. редирект бэкенда на #rewards);
    // hash приводим к простому слагу, чтобы querySelector не бросил SyntaxError
    const hashName = (location.hash.replace('#', '').match(/^[a-z-]+$/) || [])[0];
    const hashLink = hashName && tabs.querySelector(`[data-tab="${hashName}"]`);
    if (hashLink) activate(hashLink);
  });

  /* «Добавить ещё» — клонирование поля */
  document.querySelectorAll('[data-clone]').forEach(btn => {
    btn.addEventListener('click', () => {
      const field = btn.previousElementSibling;
      if (field?.classList.contains('field')) {
        const clone = field.cloneNode(true);
        const inp = clone.querySelector('input');
        if (inp) { inp.value = ''; inp.removeAttribute('readonly'); inp.removeAttribute('disabled'); }
        btn.before(clone);
      }
    });
  });

  /* Тоггл видимости пароля */
  document.querySelectorAll('.field__eye').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = btn.closest('.field__wrap')?.querySelector('input');
      if (input) input.type = input.type === 'password' ? 'text' : 'password';
    });
  });

  /* Копирование значения (ИНН/ОГРН, ссылка на проект и т.п.) — любой элемент с [data-copy] */
  document.querySelectorAll('[data-copy]').forEach(btn => {
    btn.addEventListener('click', () => { if (btn.dataset.copy) navigator.clipboard?.writeText(btn.dataset.copy); });
  });

  /* Уведомления в шапке: открыть/закрыть панель, при открытии — отметить прочитанными */
  document.querySelectorAll('[data-notif]').forEach(box => {
    const btn = box.querySelector('.notif__btn');
    const panel = box.querySelector('.notif__panel');
    const badge = box.querySelector('[data-notif-badge]');
    if (!btn || !panel) return;
    btn.addEventListener('click', e => {
      e.stopPropagation();
      const willOpen = panel.hidden;
      panel.hidden = !willOpen;
      btn.setAttribute('aria-expanded', String(willOpen));
      if (willOpen && badge && window.CININVEST && CININVEST.notif_nonce) {
        fetch(CININVEST.ajax_url, {
          method: 'POST',
          body: new URLSearchParams({ action: 'cininvest_notif_read', nonce: CININVEST.notif_nonce }),
        }).then(() => badge.remove()).catch(() => {});
      }
    });
    document.addEventListener('click', e => {
      if (!box.contains(e.target)) { panel.hidden = true; btn.setAttribute('aria-expanded', 'false'); }
    });
  });

  /* Меню аватарки в шапке: открыть/закрыть панель, клик вне — закрыть */
  document.querySelectorAll('[data-acctmenu]').forEach(box => {
    const btn = box.querySelector('.site-header__account');
    const panel = box.querySelector('.acctmenu__panel');
    if (!btn || !panel) return;
    btn.addEventListener('click', e => {
      e.stopPropagation();
      const willOpen = panel.hidden;
      panel.hidden = !willOpen;
      btn.setAttribute('aria-expanded', String(willOpen));
    });
    document.addEventListener('click', e => {
      if (!box.contains(e.target)) { panel.hidden = true; btn.setAttribute('aria-expanded', 'false'); }
    });
  });

  /* Профиль: карандаш → разблокировать поля формы, иконка меняется на галочку, повторный клик — отправка */
  document.querySelectorAll('[data-profile-edit]').forEach(btn => {
    const form = document.querySelector('[data-profile-form]');
    if (!form) return;
    btn.addEventListener('click', () => {
      if (btn.classList.contains('is-editing')) { form.requestSubmit ? form.requestSubmit() : form.submit(); return; }
      btn.classList.add('is-editing');
      btn.setAttribute('aria-label', 'Сохранить');
      form.querySelectorAll('input, select, textarea').forEach(el => {
        if (el.type === 'hidden') return;
        el.removeAttribute('readonly');
        el.removeAttribute('disabled');
      });
      form.querySelector('input:not([type=hidden]), select, textarea')?.focus();
    });
  });

  /* Профиль: выбор и превью фото (обёртка [data-photo-picker] прячет input[type=file]) */
  document.querySelectorAll('[data-photo-picker]').forEach(pick => {
    const inp = pick.querySelector('input[type="file"]');
    if (!inp) return;
    pick.addEventListener('click', e => { if (e.target !== inp) inp.click(); });
    inp.addEventListener('change', () => {
      const file = inp.files && inp.files[0];
      if (!file) return;
      let img = pick.querySelector('img');
      if (!img) { img = document.createElement('img'); img.alt = ''; pick.appendChild(img); }
      img.src = URL.createObjectURL(file);
    });
  });

  /* Регистрация: шаги роль -> форма */
  document.querySelectorAll('[data-register]').forEach(reg => {
    const steps = reg.querySelectorAll('.register__step');
    const show = name => steps.forEach(s => s.classList.toggle('is-active', s.dataset.step === name));
    reg.querySelectorAll('[data-next]').forEach(b => b.addEventListener('click', () => {
      if (b.dataset.role) {
        reg.dataset.role = b.dataset.role;
        const field = reg.querySelector('[data-role-field]');
        if (field) field.value = b.dataset.role;
      }
      show(b.dataset.next);
    }));
    reg.querySelectorAll('[data-back]').forEach(b => b.addEventListener('click', () => show(b.dataset.back)));
  });

  /* Подача заявки: шаги type -> form -> done + физ/юр */
  document.querySelectorAll('[data-appflow]').forEach(flow => {
    const steps = flow.querySelectorAll('.app-step');
    const show = name => { steps.forEach(s => s.classList.toggle('is-active', s.dataset.appstep === name)); window.scrollTo(0, 0); };
    flow.querySelectorAll('[data-type]').forEach(opt => opt.addEventListener('click', () => {
      flow.querySelectorAll('[data-type]').forEach(o => o.classList.remove('type-option--active'));
      opt.classList.add('type-option--active');
      const label = opt.dataset.type === 'ur' ? 'юрлица' : 'физлица';
      const lbl = flow.querySelector('[data-type-label]'); if (lbl) lbl.textContent = 'Продолжить как ' + label;
      const roleEl = flow.querySelector('[data-appform-role]'); if (roleEl) roleEl.textContent = label;
      const field = flow.querySelector('[data-applicant-type-field]'); if (field) field.value = opt.dataset.type;
      flow.dataset.type = opt.dataset.type;
    }));
    flow.querySelectorAll('[data-appnext]').forEach(b => b.addEventListener('click', () => {
      const target = b.dataset.appnext;
      if (target === 'form') {
        // сервер решает, какой набор секций (физ/юр) рендерить — нужен реальный переход, не просто show()
        const url = new URL(window.location.href);
        url.searchParams.set('type', flow.dataset.type || 'fiz');
        window.location.href = url.toString();
        return;
      }
      show(target);
    }));
    flow.querySelectorAll('[data-appback]').forEach(b => b.addEventListener('click', () => { if (b.dataset.appback) show(b.dataset.appback); }));

    /* Степпер внутри шага "form": 7 секций (.app-section) всегда видны, идут одна под другой;
       активный пункт .stepper__item--active подсвечивается по мере скролла (scroll-spy), клик
       по пункту степпера плавно скроллит к соответствующей секции (секции и пункты степпера
       рендерятся PHP в одном и том же порядке, поэтому индекс надёжнее сопоставления по имени —
       у физлица/юрлица разные первые секции: data vs applicant). */
    const formStep = flow.querySelector('.app-step[data-appstep="form"]');
    if (formStep) {
      const sections = Array.from(formStep.querySelectorAll('.app-section'));
      const stepperItems = Array.from(flow.querySelectorAll('.stepper__item'));
      if (sections.length && stepperItems.length === sections.length) {
        const setActive = i => stepperItems.forEach((li, k) => li.classList.toggle('stepper__item--active', k === i));

        stepperItems.forEach((li, i) => li.addEventListener('click', () => {
          sections[i].scrollIntoView({ behavior: 'smooth', block: 'start' });
        }));

        // индекс самой верхней из пересекающих viewport секций — так активный шаг
        // корректно переключается на предыдущий и при скролле вверх
        const visible = new Set();
        const observer = new IntersectionObserver(entries => {
          entries.forEach(entry => {
            const i = sections.indexOf(entry.target);
            if (entry.isIntersecting) visible.add(i); else visible.delete(i);
          });
          if (visible.size) setActive(Math.min(...visible));
        }, { threshold: 0.3 });
        sections.forEach(s => observer.observe(s));
      }
    }
  });

  /* Верификация: шаги + тип fiz/ip/ur */
  document.querySelectorAll('[data-verify]').forEach(v => {
    const steps = v.querySelectorAll('.verify-step');
    const show = name => { steps.forEach(s => s.classList.toggle('is-active', s.dataset.vstep === name)); window.scrollTo(0, 0); };

    v.querySelectorAll('[data-vt]').forEach(opt => opt.addEventListener('click', () => {
      v.querySelectorAll('[data-vt]').forEach(o => o.classList.remove('type-option--active'));
      opt.classList.add('type-option--active');
      const t = opt.dataset.vt;
      v.dataset.vtype = t;
      const label = { fiz: 'физлицо', ip: 'ИП', ur: 'юрлицо' }[t];
      const lbl = v.querySelector('[data-vt-label]'); if (lbl) lbl.textContent = 'Продолжить как ' + label;
      const field = v.querySelector('[data-vtype-field]'); if (field) field.value = t;

      const stepTitle = { fiz: 'Верификация физлица', ip: 'Верификация ИП', ur: 'Верификация юрлица' }[t];
      v.querySelectorAll('[data-vstep-title]').forEach(h => h.textContent = stepTitle);

      const ogrnText = (t === 'ip') ? 'ОГРНИП' : 'ОГРН';
      v.querySelectorAll('[data-ogrn-label]').forEach(el => {
        const target = el.querySelector('.field__label, dt') || el;
        target.textContent = ogrnText;
      });
      const egripText = (t === 'ur') ? 'ЕГРЮЛ' : 'ЕГРИП';
      v.querySelectorAll('[data-egrip]').forEach(el => {
        const label = el.querySelector('.field__label'); if (label) label.textContent = 'Копия выписки из ' + egripText;
        const text = el.querySelector('.file-upload__text'); if (text) text.textContent = 'Загрузите копию выписки из ' + egripText;
      });
      v.querySelectorAll('[data-ur-only]').forEach(el => el.hidden = (t !== 'ur'));
      v.querySelectorAll('[data-not-fiz]').forEach(el => el.hidden = (t === 'fiz'));

      // физлицо: паспорт -> вопрос про ПДЛ -> проверка данных -> почти готово.
      // ИП/юрлицо: паспорт -> юр.данные -> реквизиты счёта -> проверка данных -> почти готово (без ПДЛ).
      const passportNextBtn = v.querySelector('[data-vstep="passport"] [data-vnext]');
      if (passportNextBtn) passportNextBtn.dataset.vnext = (t === 'fiz') ? 'pdl' : 'legal';
      const checkingBackBtn = v.querySelector('[data-vstep="checking"] [data-vback]');
      if (checkingBackBtn) checkingBackBtn.dataset.vback = (t === 'fiz') ? 'pdl' : 'account';
    }));

    v.querySelectorAll('[data-pdl-answer]').forEach(b => b.addEventListener('click', () => {
      const field = v.querySelector('[data-pdl-field]');
      if (field) field.value = b.dataset.pdlAnswer;
    }));

    v.querySelectorAll('[data-vnext]').forEach(b => b.addEventListener('click', () => show(b.dataset.vnext)));
    v.querySelectorAll('[data-vback]').forEach(b => b.addEventListener('click', () => { if (b.dataset.vback) show(b.dataset.vback); }));
    v.querySelectorAll('[data-vlater]').forEach(b => b.addEventListener('click', () => { window.location.href = '/'; }));
  });

  /* Модалки: открытие/закрытие */
  document.querySelectorAll('[data-open]').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      const m = document.getElementById(btn.dataset.open);
      if (m) { m.hidden = false; document.body.style.overflow = 'hidden'; }
    });
  });
  function closeModal(m) { m.hidden = true; document.body.style.overflow = ''; }
  document.querySelectorAll('.modal').forEach(m => {
    m.querySelectorAll('[data-close]').forEach(el => el.addEventListener('click', () => closeModal(m)));
  });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.modal:not([hidden])').forEach(closeModal);
  });

  /* Раунды на странице проекта */
  document.querySelectorAll('.project-hero__rounds').forEach(r => {
    r.querySelectorAll('.round-tab').forEach(t => t.addEventListener('click', () => {
      r.querySelectorAll('.round-tab').forEach(x => x.classList.remove('round-tab--active'));
      t.classList.add('round-tab--active');
    }));
  });

  /* Вкладка «Вознаграждения» на странице проекта: пресеты суммы, живой пересчёт суммы по чекбоксам
     в скрытое поле total перед отправкой формы (сама отправка — обычный POST на admin-post.php) */
  document.querySelectorAll('.reward-block').forEach(block => {
    const input = block.querySelector('.reward-block__input');
    const options = block.querySelectorAll('.reward-option input[type="checkbox"]');
    const totalOut = block.querySelector('[data-rewards-total]');
    const totalInput = block.querySelector('[data-perks-total-input]');

    block.querySelectorAll('.preset').forEach(p => p.addEventListener('click', () => {
      if (input) input.value = p.textContent.replace(/\D/g, '');
    }));

    const recalcTotal = () => {
      const sum = Array.from(options).reduce((acc, cb) => acc + (cb.checked ? (parseFloat(cb.dataset.perkPrice) || 0) : 0), 0);
      // data-rewards-amount (кнопка «Купить» на странице проекта) — только голая сумма, без
      // префикса, и пусто, пока ничего не выбрано, чтобы не затирать слово «Купить»;
      // без этого атрибута (лейбл «Итого: …» в модалке спонсора) — префикс всегда виден
      if (totalOut) {
        totalOut.textContent = totalOut.hasAttribute('data-rewards-amount')
          ? (sum > 0 ? sum.toLocaleString('ru-RU') + ' ₽' : '')
          : 'Итого: ' + sum.toLocaleString('ru-RU') + ' ₽';
      }
      if (totalInput) totalInput.value = sum;
    };
    options.forEach(cb => cb.addEventListener('change', recalcTotal));
    recalcTotal();
  });

  /* AJAX-калькулятор покупки долей: сумма <-> доли <-> комиссия (живой пересчёт) */
  document.querySelectorAll('[data-buy-form]').forEach(form => {
    const projectId = form.dataset.project;
    const amountInput = form.querySelector('[data-calc-amount]');
    const sharesInput = form.querySelector('[data-calc-shares]');
    const out = key => form.querySelector(`[data-out="${key}"]`);

    async function recalc(byShares) {
      if (!window.CININVEST) return;
      const body = new URLSearchParams({
        action: 'cininvest_calc_shares',
        nonce: CININVEST.shares_nonce,
        project_id: projectId,
      });
      if (byShares) body.set('shares', sharesInput.value || 0);
      else body.set('amount', amountInput.value || 0);

      try {
        const res = await fetch(CININVEST.ajax_url, { method: 'POST', body });
        const json = await res.json();
        if (!json.success) return;
        const d = json.data;
        sharesInput.value = d.shares;
        amountInput.value = d.base;
        if (out('shares')) out('shares').textContent = d.shares;
        if (out('share_price')) out('share_price').textContent = d.share_price + ' ₽';
        if (out('base')) out('base').textContent = d.base + ' ₽';
        if (out('commission')) out('commission').textContent = d.commission + ' ₽';
        const totalOut = out('total');
        if (totalOut) totalOut.textContent = d.total_fmt;
      } catch (e) { /* сеть недоступна — молча оставляем текущие значения */ }
    }

    if (sharesInput) sharesInput.addEventListener('input', () => recalc(true));
    if (amountInput) amountInput.addEventListener('input', () => recalc(false));
    if (sharesInput) recalc(true); // первичный расчёт при открытии
  });

});
