if (!window.assistantWidgetLoaded) {
    window.assistantWidgetLoaded = true;

    document.addEventListener("DOMContentLoaded", () => {

        const bubble = document.getElementById("assistantWidgetOpen");
        const panel  = document.getElementById("assistantWidgetPanel");
        const body   = document.getElementById("assistantWidgetBody");

        if (!bubble || !panel || !body) return;

        // Ouvre / ferme le widget
        bubble.addEventListener("click", () => {
            panel.classList.toggle("open");
        });

        // Gestion boutons internes
        body.addEventListener("click", (e) => {
            if (e.target.classList.contains("assistant-cat-btn")) {
                openChatWithCategory(
                    e.target.dataset.cat,
                    e.target.textContent.trim()
                );
            }
            if (e.target.id === "assistantReturnHome") {
                loadHomeMenu();
            }
        });

        // ======================
        // 📌  MENU PRINCIPAL
        // ======================
        async function loadHomeMenu() {
            body.innerHTML = `
                <div class="assistant-msg">
                    Bonjour 👋 je suis <strong>Elios</strong> !<br>
                    Je peux t’aider à retrouver ton numéro de licence 🎟️.
                </div>
                <div id="assistantCategories" class="assistant-categories">
                    <div class="assistant-msg info">Chargement…</div>
                </div>`;

            try {
                const r = await fetch("/assistant/categories");
                const cats = await r.json();

                const container = document.getElementById("assistantCategories");
                container.innerHTML = "";

                cats.forEach(x => {
                    const btn = document.createElement("button");
                    btn.className = "assistant-cat-btn";
                    btn.dataset.cat = x.category;
                    btn.textContent = `${x.icon || ""} ${x.label}`;
                    container.appendChild(btn);
                });

            } catch {
                document.getElementById("assistantCategories").innerHTML =
                    "<div class='assistant-msg'>⛔ Impossible de charger les catégories.</div>";
            }
        }

        // ======================
        // 📌  OUVERTURE D’UNE CATÉGORIE
        // ======================
        function openChatWithCategory(cat, label) {
            body.innerHTML = `
                <div id="assistantChatArea" class="assistant-chat-area">
                    <div class="assistant-msg user">${label}</div>
                </div>
                <div class="assistant-chat-input">
                    <input id="assistantChatInput" type="text" placeholder="Écris ici…">
                    <button id="assistantChatSend">➤</button>
                </div>`;

            const area = document.getElementById("assistantChatArea");
            const input = document.getElementById("assistantChatInput");
            const send  = document.getElementById("assistantChatSend");

            if (cat === "license") {
                handleLicenseFlow(area, input, send);
            }
        }

        // ======================
        // 📌  FLUX : RÉCUPÉRATION DE LICENCE
        // ======================
        function handleLicenseFlow(area, input, send) {
            let step = 0;
            let form  = { firstName: "", lastName: "" };
            let token = null;

            append(area, "Je peux t’aider à retrouver ton numéro de licence. Ton prénom ?", "bot");

            const process = async () => {
                const val = input.value.trim();
                if (!val) return;

                append(area, val, "user");
                input.value = "";

                switch (step) {

                    case 0:
                        form.firstName = val;
                        append(area, "Merci 😊 et ton nom de famille ?", "bot");
                        step++;
                        break;

                    case 1:
                        form.lastName = val;
                        showTyping(area);

                        try {
                            const r = await fetch("/assistant/license/start", {
                                method: "POST",
                                headers: { "Content-Type": "application/json" },
                                body: JSON.stringify(form)
                            });

                            const d = await r.json();
                            hideTyping(area);

                            if (d.ok && d.token) {
                                token = d.token;
                                step++;
                                append(area, "📧 Un code à 6 chiffres t’a été envoyé.", "bot");
                            } else {
                                append(area, d.error || "⛔ Aucun adhérent trouvé.", "bot");
                                appendReturn(area);
                            }

                        } catch {
                            hideTyping(area);
                            append(area, "⛔ Erreur serveur.", "bot");
                        }

                        break;

                    case 2:
                        const code = val.replace(/\D/g, "");
                        if (code.length !== 6) {
                            append(area, "Le code doit contenir 6 chiffres.", "bot");
                            return;
                        }

                        showTyping(area);

                        try {
                            const r = await fetch("/assistant/license/verify", {
                                method: "POST",
                                headers: { "Content-Type": "application/json" },
                                body: JSON.stringify({ token, code })
                            });

                            const d = await r.json();
                            hideTyping(area);

                            if (d.ok) {
                                append(area, "✔ Code validé !", "bot");

                                setTimeout(() => {
                                    append(area,
                                        `🎉 Vérification réussie !<br><br>
                                         <strong>Numéro de licence :</strong> ${d.license}<br>
                                         (au nom de ${d.fullName})`,
                                        "bot"
                                    );
                                    appendReturn(area);
                                }, 600);

                            } else {
                                append(area, "✖ Code invalide ou expiré.", "bot");
                                appendReturn(area);
                            }

                        } catch {
                            hideTyping(area);
                            append(area, "⛔ Erreur de communication serveur.", "bot");
                            appendReturn(area);
                        }
                        break;
                }
            };

            send.addEventListener("click", process);
            input.addEventListener("keydown", e => {
                if (e.key === "Enter") process();
            });
        }

        // UTILITAIRES
        function append(container, msg, type = "bot") {
            const div = document.createElement("div");
            div.classList.add("assistant-msg", type);
            div.innerHTML = msg;
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }

        function showTyping(container) {
            const div = document.createElement("div");
            div.classList.add("assistant-msg", "bot");
            div.id = "eliosTyping";
            div.innerHTML = "<div class='assistant-typing'><span></span><span></span><span></span></div>";
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }

        function hideTyping() {
            const div = document.getElementById("eliosTyping");
            if (div) div.remove();
        }

        function appendReturn(container) {
            if (document.getElementById("assistantReturnHome")) return;
            const btn = document.createElement("div");
            btn.className = "assistant-actions";
            btn.innerHTML = `
                <button id="assistantReturnHome" class="assistant-btn">
                    🏠 Retour à l’accueil
                </button>`;
            container.appendChild(btn);
            container.scrollTop = container.scrollHeight;
        }

        loadHomeMenu();
    });
}
