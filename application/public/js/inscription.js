document.addEventListener('DOMContentLoaded', () => {
    const departement = document.querySelector('[data-filtre-departement]');
    const listes = [document.querySelector('#service'), document.querySelector('#n1')];
    const optionsParListe = new Map(listes.map((liste) => [liste, [...liste.querySelectorAll('option[data-departement]')]]));

    const filtrer = () => {
        listes.forEach((liste) => {
            const choixActuel = liste.value;
            const options = optionsParListe.get(liste);
            options.forEach((option) => option.remove());

            const duDepartement = options.filter((option) => option.dataset.departement === departement.value);
            duDepartement.forEach((option) => liste.append(option));

            liste.value = duDepartement.some((option) => option.value === choixActuel) ? choixActuel : '';
            liste.disabled = departement.value === '';
            liste.options[0].textContent = departement.value === '' ? "Choisissez d'abord le département" : 'Choisir…';
        });
    };

    departement.addEventListener('change', filtrer);
    filtrer();
});
