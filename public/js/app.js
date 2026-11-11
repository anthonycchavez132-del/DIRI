'use strict';

// Mejora opcional: todos los módulos siguen accesibles si JavaScript está apagado.
const filterBox = document.querySelector('[data-module-filter]');
if (filterBox) {
    const input = filterBox.querySelector('[data-module-query]');
    const counter = filterBox.querySelector('[data-module-count]');
    const cards = [...document.querySelectorAll('[data-module-list] > article')];
    const normalize = text => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');
    filterBox.hidden = false;
    input.addEventListener('input', () => {
        const query = normalize(input.value.trim());
        let visible = 0;
        cards.forEach(card => {
            card.hidden = !normalize(card.textContent).includes(query);
            if (!card.hidden) visible++;
        });
        counter.textContent = `${visible} herramientas disponibles`;
    });
}

// Progreso temporal de las guías gratuitas. Solo guarda índices de pasos en esta
// pestaña; no envía información al servidor ni modifica el seguimiento Premium.
const simpleGuide = document.querySelector('[data-simple-guide]');
if (simpleGuide) {
    const checks = [...simpleGuide.querySelectorAll('[data-guide-done]')];
    const key = `diri-guide-beta:${simpleGuide.dataset.guideKey}`;
    const summary = simpleGuide.querySelector('[data-guide-summary]');
    const progress = simpleGuide.querySelector('[data-guide-progress]');
    const storageNote = simpleGuide.querySelector('[data-guide-storage]');
    const unavailableStorage = () => {
        storageNote.textContent = 'El navegador no permite conservar las marcas en esta pestaña. Se reiniciarán al recargar; no se guardan en tu cuenta.';
    };
    try {
        const saved = JSON.parse(sessionStorage.getItem(key) || '[]');
        if (Array.isArray(saved)) checks.forEach(check => { check.checked = saved.includes(check.value); });
    } catch { unavailableStorage(); }
    const update = () => {
        const done = checks.filter(check => check.checked);
        summary.textContent = `${done.length} de ${checks.length} pasos marcados`;
        progress.value = done.length;
        checks.forEach(check => check.closest('[data-guide-step]').classList.toggle('guide-step-done', check.checked));
    };
    const save = () => {
        try { sessionStorage.setItem(key, JSON.stringify(checks.filter(check => check.checked).map(check => check.value))); }
        catch { unavailableStorage(); }
        update();
    };
    checks.forEach(check => check.addEventListener('change', save));
    simpleGuide.querySelector('[data-guide-reset]').addEventListener('click', () => {
        checks.forEach(check => { check.checked = false; });
        save();
    });
    simpleGuide.querySelectorAll('[data-guide-controls], [data-guide-control]').forEach(element => { element.hidden = false; });
    update();
}
