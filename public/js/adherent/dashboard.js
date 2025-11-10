document.addEventListener('DOMContentLoaded', () => {

    // --- 🔹 Fonction : Ouvre la modale ---
    function openLicenceModal() {
        const modal = document.getElementById('licence-modal');
        if (modal) modal.style.display = 'flex';
    }

    // --- 🔹 Fonction : Initialise le bouton "Ajouter ma licence" ---
    function initAddLicenceButton() {
        const addBtn = document.getElementById('add-licence-btn');
        if (addBtn) {
            addBtn.replaceWith(addBtn.cloneNode(true)); // évite doublons de listeners
            const newBtn = document.getElementById('add-licence-btn');
            newBtn.addEventListener('click', openLicenceModal);
        }
    }

    // --- 🔹 Fonction : Mets à jour dynamiquement les avantages ---
    function updateBenefits(active = true) {
        const benefitsSection = document.querySelector('.benefits-section');
        if (!benefitsSection) return;

        if (active) {
            benefitsSection.innerHTML = `
                <h4>Avantages membres</h4>
                <ul class="benefits-list highlight-benefits">
                    <li>✔️ Accès complet à la salle d’entraînement</li>
                    <li>✔️ Coaching personnalisé sur demande</li>
                    <li>✔️ Participation aux compétitions</li>
                </ul>
            `;
        } else {
            benefitsSection.innerHTML = `
                <h4>Avantages membres</h4>
                <ul class="benefits-list">
                    <li>❌ Aucun avantage — licence non active</li>
                    <li>💬 Astuce : retrouve ton numéro de licence via le chatbot “Elios”.</li>
                </ul>
            `;
        }
    }

    // --- 🔹 Fonction : Initialise la suppression de licence ---
    function initRemoveLicenceLogic() {
        const removeBtn = document.getElementById('remove-licence-btn');
        const removeMsg = document.getElementById('remove-licence-message');

        if (!removeBtn) return;

        removeBtn.addEventListener('click', async () => {
            const confirmDelete = confirm("⚠️ Êtes-vous sûr de vouloir supprimer votre licence de ce compte ?");
            if (!confirmDelete) return;

            removeMsg.innerHTML = "⏳ Suppression en cours...";
            removeMsg.style.color = "#555";

            try {
                const response = await fetch(removeBtn.dataset.removeUrl, { method: 'POST' });
                const data = await response.json();

                if (data.success) {
                    removeMsg.innerHTML = "✅ " + data.message;
                    removeMsg.style.color = "green";

                    const licenceDisplay = document.querySelector('.licence-number');
                    const statusBadge = document.querySelector('.status-badge');
                    const expiryDate = document.querySelector('.expiry-date');
                    const infoGroup = removeBtn.closest('.info-group');

                    if (licenceDisplay) licenceDisplay.textContent = "—";
                    if (statusBadge) {
                        statusBadge.textContent = "Expirée";
                        statusBadge.classList.remove('success');
                        statusBadge.classList.add('error');
                    }
                    if (expiryDate) expiryDate.textContent = "Non définie";

                    updateBenefits(false);

                    setTimeout(() => {
                        if (infoGroup) {
                            infoGroup.innerHTML = `
                                <button class="btn-primary" id="add-licence-btn">Ajouter mon numéro de licence</button>
                            `;
                            initAddLicenceButton();
                        }
                        removeMsg.innerHTML = "";
                    }, 800);
                } else {
                    removeMsg.innerHTML = "❌ " + data.message;
                    removeMsg.style.color = "red";
                }
            } catch (err) {
                console.error(err);
                removeMsg.innerHTML = "❌ Erreur lors de la suppression.";
                removeMsg.style.color = "red";
            }
        });
    }

    // --- 🔹 Gestion du formulaire d’ajout ---
    const form = document.getElementById('licence-form');
    const messageBox = document.getElementById('licence-message');
    const licenceModal = document.getElementById('licence-modal');
    const addLicenceBtn = document.getElementById('add-licence-btn');

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            messageBox.innerHTML = '⏳ Vérification en cours...';
            messageBox.style.color = '#555';

            const formData = new FormData(form);

            try {
                const response = await fetch(form.action, { method: 'POST', body: formData });
                const data = await response.json();

                if (data.success) {
                    messageBox.innerHTML = '✅ ' + data.message;
                    messageBox.style.color = 'green';
                    setTimeout(() => { licenceModal.style.display = 'none'; }, 1000);

                    const licenceDisplay = document.querySelector('.licence-number');
                    if (licenceDisplay) licenceDisplay.textContent = data.licenceNumber;

                    const statusBadge = document.querySelector('.status-badge');
                    if (statusBadge) {
                        statusBadge.textContent = data.status;
                        statusBadge.classList.remove('error');
                        statusBadge.classList.add('success');
                    }

                    const expiryDate = document.querySelector('.expiry-date');
                    if (expiryDate) expiryDate.textContent = data.expiryDate;

                    updateBenefits(true);
                    if (addLicenceBtn) addLicenceBtn.style.display = 'none';
                } else {
                    messageBox.innerHTML = '❌ ' + data.message;
                    messageBox.style.color = 'red';
                }
            } catch (err) {
                console.error(err);
                messageBox.innerHTML = '❌ Erreur lors de la vérification.';
                messageBox.style.color = 'red';
            }
        });
    }

    // --- Initialisation globale ---
    initAddLicenceButton();
    initRemoveLicenceLogic();

});
