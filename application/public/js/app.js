document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-auto-fermeture]').forEach((message) => {
        setTimeout(() => {
            message.classList.add('disparait');
            setTimeout(() => message.remove(), 400);
        }, 5000);
    });

    document.querySelectorAll('[data-filtre-tableau]').forEach((champ) => {
        const tableau = document.querySelector(champ.dataset.filtreTableau);
        champ.addEventListener('input', () => {
            const texte = champ.value.toLowerCase();
            tableau.querySelectorAll('tbody tr').forEach((ligne) => {
                const correspond = ligne.textContent.toLowerCase().includes(texte);
                ligne.style.display = correspond ? '' : 'none';
            });
        });
    });
});
