document.addEventListener('DOMContentLoaded', () => {
    const pad = document.querySelector('#pad');
    const crayon = pad.getContext('2d');
    const boutonValider = document.querySelector('#valider');
    const formulaire = document.querySelector('#formulaireSignature');
    const essais = [null, null, null];
    let essaiEnCours = 0;
    let traits = [];
    let enTrainDeDessiner = false;

    const ratio = window.devicePixelRatio || 1;
    pad.width = pad.clientWidth * ratio;
    pad.height = pad.clientHeight * ratio;
    crayon.scale(ratio, ratio);
    crayon.lineWidth = 2.2;
    crayon.lineCap = 'round';
    crayon.lineJoin = 'round';
    crayon.strokeStyle = '#2F3437';

    const position = (evenement) => {
        const cadre = pad.getBoundingClientRect();
        return [Math.round(evenement.clientX - cadre.left), Math.round(evenement.clientY - cadre.top)];
    };

    const redessiner = () => {
        crayon.clearRect(0, 0, pad.width, pad.height);
        traits.forEach((trait) => {
            crayon.beginPath();
            trait.forEach(([x, y], i) => (i === 0 ? crayon.moveTo(x, y) : crayon.lineTo(x, y)));
            crayon.stroke();
        });
        boutonValider.disabled = traits.length === 0;
    };

    pad.addEventListener('pointerdown', (evenement) => {
        enTrainDeDessiner = true;
        traits.push([position(evenement)]);
        pad.setPointerCapture(evenement.pointerId);
    });
    pad.addEventListener('pointermove', (evenement) => {
        if (!enTrainDeDessiner) {
            return;
        }
        traits.at(-1).push(position(evenement));
        redessiner();
    });
    pad.addEventListener('pointerup', () => {
        enTrainDeDessiner = false;
        redessiner();
    });

    document.querySelector('#effacer').addEventListener('click', () => {
        traits = [];
        redessiner();
    });

    const afficherEssai = (numero) => {
        essaiEnCours = numero;
        traits = essais[numero] ? essais[numero].traits.map((trait) => trait.slice()) : [];
        redessiner();

        document.querySelector('#titreEssai').textContent = `Essai ${numero + 1} sur 3`;
        document.querySelectorAll('#etapes li').forEach((etape, i) => {
            etape.className = i === numero ? 'en-cours' : essais[i] ? 'fait' : '';
        });

        const dernierManquant = essais.filter((essai, i) => essai === null && i !== numero).length === 0;
        boutonValider.textContent = dernierManquant ? 'Enregistrer ma signature' : `Valider l'essai ${numero + 1}`;

        const vignettes = document.querySelector('#vignettes');
        vignettes.querySelectorAll('.vignette').forEach((vignette) => vignette.remove());
        essais.forEach((essai, i) => {
            const vignette = document.createElement('button');
            vignette.type = 'button';
            vignette.className = 'vignette' + (i === numero ? ' en-cours' : '');
            vignette.innerHTML = `<b>Essai ${i + 1}</b>` + (essai && i !== numero ? `<img src="${essai.image}" alt="">` : '');
            vignette.disabled = !essai || i === numero;
            vignette.addEventListener('click', () => afficherEssai(i));
            vignettes.append(vignette);
        });
    };

    boutonValider.addEventListener('click', () => {
        essais[essaiEnCours] = { traits: traits.map((trait) => trait.slice()), image: pad.toDataURL('image/png') };

        const prochain = essais.findIndex((essai) => essai === null);
        if (prochain !== -1) {
            afficherEssai(prochain);
            return;
        }

        document.querySelector('#essais').value = JSON.stringify(essais);
        boutonValider.disabled = true;
        boutonValider.textContent = 'Enregistrement…';
        formulaire.submit();
    });

    afficherEssai(0);
});
