if (!window.assistantWidgetLoaded) {
	window.assistantWidgetLoaded = true;

	document.addEventListener("DOMContentLoaded", () => {
		console.log("✅ Elios (assistant.js) prêt avec gestion intelligente des licences.");

		const bubble = document.getElementById("assistantWidgetOpen");
		const panel = document.getElementById("assistantWidgetPanel");
		const body = document.getElementById("assistantWidgetBody");

		if (!bubble || !panel || !body) return;

		bubble.addEventListener("click", () => {
			panel.classList.toggle("open");
		});

		body.addEventListener("click", (e) => {
			if (e.target.classList.contains("assistant-cat-btn")) {
				const cat = e.target.dataset.cat;
				const label = e.target.textContent.trim();
				openChatWithCategory(cat, label);
			}

			if (e.target.id === "assistantReturnHome") {
				loadHomeMenu();
			}
		});

		// ===============================
		//   ACCUEIL DU CHAT (depuis la BDD)
		// ===============================
		async function loadHomeMenu() {
			body.innerHTML = `
				<div class="assistant-msg">
					Bonjour, je m’appelle <strong>Elios</strong><br>
					Je peux vous aider sur vos questions liées au club, aux licences, ou même à l’entraînement !
				</div>

				<div id="assistantCategories" class="assistant-categories">
					<div class="assistant-msg info">Chargement des options...</div>
				</div>
			`;

			try {
				const res = await fetch("/assistant/categories");
				const cats = await res.json();
				const container = document.getElementById("assistantCategories");
				container.innerHTML = "";

				if (!cats.length) {
					container.innerHTML = `<div class="assistant-msg info">Aucune catégorie disponible pour le moment.</div>`;
					return;
				}

				cats.forEach(cat => {
					container.insertAdjacentHTML("beforeend", `
						<button class="assistant-cat-btn" data-cat="${cat.category}">
							${cat.icon || ""} ${cat.label}
						</button>
					`);
				});
			} catch {
				document.getElementById("assistantCategories").innerHTML =
					`<div class="assistant-msg">⛔ Impossible de charger les catégories.</div>`;
			}
		}

		// ===============================
		//   OUVERTURE DU CHAT (catégorie)
		// ===============================
		function openChatWithCategory(cat, label) {
			body.innerHTML = `
				<div id="assistantChatArea" class="assistant-chat-area">
					<div class="assistant-msg user">${label}</div>
				</div>
				<div class="assistant-chat-input">
					<input id="assistantChatInput" type="text" placeholder="Posez une question à Elios...">
					<button id="assistantChatSend">➤</button>
				</div>
			`;

			const chatArea = document.getElementById("assistantChatArea");
			const chatInput = document.getElementById("assistantChatInput");
			const chatSend = document.getElementById("assistantChatSend");

			if (cat === "license") {
				handleLicenseFlow(chatArea, chatInput, chatSend);
				return;
			}


			sendMessage(cat, false);


			chatSend.addEventListener("click", () => {
				const msg = chatInput.value.trim();
				if (msg) sendMessage(msg);
			});
			chatInput.addEventListener("keydown", (e) => {
				if (e.key === "Enter" && chatInput.value.trim()) sendMessage(chatInput.value.trim());
			});

			async function sendMessage(message, showUserMessage = true) {
	if (showUserMessage) appendMessage(chatArea, message, "user");
	showTyping(chatArea);


				try {
					const res = await fetch("/assistant/chat", {
						method: "POST",
						headers: { "Content-Type": "application/json" },
						body: JSON.stringify({ message }),
					});
					const data = await res.json();
					hideTyping(chatArea);
					appendMessage(chatArea, data.reply || "Je n’ai pas compris 😅", "bot");
				} catch {
					hideTyping(chatArea);
					appendMessage(chatArea, "⛔ Erreur de communication avec le serveur.", "bot");
				}

				appendReturnButton(chatArea);
			}
		}

		// ===============================
		//   FLUX SPÉCIAL LICENCE 🎟️
		// ===============================
		function handleLicenseFlow(chatArea, chatInput, chatSend) {
			let step = 0;
			let formData = { firstName: "", lastName: "", email: "" };

			appendMessage(chatArea, "Je peux t’aider à retrouver ton numéro de licence. Peux-tu me donner ton prénom ?", "bot");

			chatSend.addEventListener("click", () => processInput());
			chatInput.addEventListener("keydown", (e) => { if (e.key === "Enter") processInput(); });

			async function processInput() {
				const input = chatInput.value.trim();
				if (!input) return;
				appendMessage(chatArea, input, "user");
				chatInput.value = "";

				switch (step) {
					case 0:
						formData.firstName = input;
						appendMessage(chatArea, "Merci 😊 Et ton nom de famille ?", "bot");
						step++;
						break;

					case 1:
						formData.lastName = input;
						appendMessage(chatArea, "Parfait ! Maintenant ton adresse e-mail ?", "bot");
						step++;
						break;

					case 2:
						formData.email = input;
						showTyping(chatArea);
						step++;

						try {
							const res = await fetch("/assistant/license/start", {
								method: "POST",
								headers: { "Content-Type": "application/json" },
								body: JSON.stringify(formData),
							});
							const data = await res.json();
							hideTyping(chatArea);

							if (data.ok) {
								appendMessage(chatArea, data.message || "✅ Email de confirmation envoyé !", "bot");
							} else {
								appendMessage(chatArea, data.error || "⛔ Une erreur est survenue lors de la vérification.", "bot");
							}
						} catch (err) {
							hideTyping(chatArea);
							appendMessage(chatArea, "⛔ Erreur de communication avec le serveur.", "bot");
						}

						appendReturnButton(chatArea);
						break;
				}
			}
		}

		// ===============================
		//   UTILITAIRES AFFICHAGE
		// ===============================
		function appendMessage(container, text, sender = "bot") {
			const msg = document.createElement("div");
			msg.classList.add("assistant-msg", sender);
			msg.textContent = text;
			container.appendChild(msg);
			container.scrollTop = container.scrollHeight;
		}

		function showTyping(container) {
			const typing = document.createElement("div");
			typing.classList.add("assistant-msg", "bot");
			typing.id = "eliosTyping";
			typing.innerHTML = `<div class="assistant-typing"><span></span><span></span><span></span></div>`;
			container.appendChild(typing);
			container.scrollTop = container.scrollHeight;
		}

		function hideTyping(container) {
			const typing = document.getElementById("eliosTyping");
			if (typing) typing.remove();
			container.scrollTop = container.scrollHeight;
		}

		function appendReturnButton(container) {
			if (document.getElementById("assistantReturnHome")) return;
			const btnWrapper = document.createElement("div");
			btnWrapper.classList.add("assistant-actions");
			btnWrapper.innerHTML = `
				<button class="assistant-btn" id="assistantReturnHome" style="
					background:#e5e7eb;
					color:#111;
					font-weight:600;
					padding:6px 12px;
					border-radius:10px;
					font-size:0.85rem;
				">🏠 Retour à l'accueil</button>
			`;
			container.appendChild(btnWrapper);
			container.scrollTop = container.scrollHeight;
		}

		loadHomeMenu();
	});
}
