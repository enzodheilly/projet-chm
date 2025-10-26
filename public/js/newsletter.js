document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('newsletter-form');
    if (!form) {
        console.warn("⚠️ Formulaire newsletter non trouvé.");
        return;
    }

    console.log("✅ Script newsletter chargé correctement.");
    console.log("Form trouvé :", form);

    const successMsg = document.getElementById('newsletterSuccess');
    const errorMsg = document.getElementById('newsletterError');
    let submitted = false;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (submitted) return;
        submitted = true;

        successMsg.textContent = '';
        errorMsg.textContent = '';

        const formData = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                throw new Error("Réponse non JSON reçue");
            }

            const data = await response.json();
            console.log("🔹 Réponse newsletter :", data);

            if (data.success) {
                successMsg.textContent = data.message || "✅ Merci ! Vous êtes abonné à notre newsletter.";
                successMsg.classList.add('show');
                form.reset();
                setTimeout(() => successMsg.classList.remove('show'), 3000);
            } else {
                errorMsg.textContent = data.message || "❌ Une erreur est survenue, veuillez réessayer.";
            }

        } catch (err) {
            console.error("Erreur newsletter :", err);
            errorMsg.textContent = "⚠️ Une erreur est survenue, veuillez réessayer.";
        } finally {
            submitted = false;
        }
    });
});
