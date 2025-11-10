if (!window.assistantWidgetLoaded) {
  window.assistantWidgetLoaded = true;

  document.addEventListener("DOMContentLoaded", () => {
    console.log("✅ Elios prêt avec vérification automatique du code.");

    const bubble = document.getElementById("assistantWidgetOpen");
    const panel  = document.getElementById("assistantWidgetPanel");
    const body   = document.getElementById("assistantWidgetBody");
    if (!bubble || !panel || !body) return;

    bubble.addEventListener("click",()=>panel.classList.toggle("open"));
    body.addEventListener("click",e=>{
      if(e.target.classList.contains("assistant-cat-btn"))
        openChatWithCategory(e.target.dataset.cat,e.target.textContent.trim());
      if(e.target.id==="assistantReturnHome")loadHomeMenu();
    });

    async function loadHomeMenu(){
      body.innerHTML=`<div class="assistant-msg">
        Bonjour 👋 je suis <strong>Elios</strong> !<br>
        Je peux t’aider à retrouver ton numéro de licence 🎟️.
      </div>
      <div id="assistantCategories" class="assistant-categories">
        <div class="assistant-msg info">Chargement…</div>
      </div>`;
      try{
        const r=await fetch("/assistant/categories");
        const cats=await r.json();
        const c=document.getElementById("assistantCategories");
        c.innerHTML="";
        cats.forEach(x=>c.insertAdjacentHTML("beforeend",
          `<button class="assistant-cat-btn" data-cat="${x.category}">
            ${x.icon||""} ${x.label}</button>`));
      }catch{body.querySelector("#assistantCategories").innerHTML=
        "<div class='assistant-msg'>⛔ Impossible de charger les catégories.</div>";}
    }

    function openChatWithCategory(cat,label){
      body.innerHTML=`<div id="assistantChatArea" class="assistant-chat-area">
          <div class="assistant-msg user">${label}</div></div>
        <div class="assistant-chat-input">
          <input id="assistantChatInput" type="text" placeholder="Écris ici…">
          <button id="assistantChatSend">➤</button>
        </div>`;
      const area=document.getElementById("assistantChatArea");
      const input=document.getElementById("assistantChatInput");
      const send=document.getElementById("assistantChatSend");
      if(cat==="license"){handleLicenseFlow(area,input,send);return;}
    }

    function handleLicenseFlow(area,input,send){
      let step=0,form={firstName:"",lastName:""},token=null;
      append(area,"Je peux t’aider à retrouver ton numéro de licence. Ton prénom ?","bot");

      send.addEventListener("click",()=>process());
      input.addEventListener("keydown",e=>{if(e.key==="Enter")process();});

      async function process(){
        const val=input.value.trim(); if(!val) return;
        append(area,val,"user"); input.value="";
        switch(step){
          case 0:
            form.firstName=val;
            append(area,"Merci 😊 et ton nom de famille ?","bot");
            step++; break;

          case 1:
            form.lastName=val; showTyping(area);
            try{
              const r=await fetch("/assistant/license/start",{
                method:"POST",headers:{"Content-Type":"application/json"},
                body:JSON.stringify(form)
              });
              const d=await r.json(); hideTyping(area);
              if(d.ok&&d.token){
                token=d.token; step++;
                append(area,"📧 Un code à 6 chiffres t’a été envoyé. Écris-le ici dès que tu l’as reçu :","bot");
              }else{
                append(area,d.error||"⛔ Aucun adhérent trouvé.","bot");
                appendReturn(area);
              }
            }catch{hideTyping(area);append(area,"⛔ Erreur serveur.","bot");}
            break;

          case 2:
	const code = val.replace(/\D/g, "");
	if (code.length !== 6) {
		append(area, "Le code doit contenir 6 chiffres.", "bot");
		return;
	}

	append(area, "🔄 Vérification du code en cours…", "bot");
	showTyping(area);

	try {
		const r = await fetch("/assistant/license/verify", {
			method: "POST",
			headers: { "Content-Type": "application/json" },
			body: JSON.stringify({ token, code })
		});
		const d = await r.json();
		hideTyping(area);

		console.log("✅ Réponse /assistant/license/verify :", d);

		if (d.ok) {
			// 💚 Animation "code validé"
			const successDiv = document.createElement("div");
			successDiv.classList.add("assistant-msg", "bot");
			successDiv.innerHTML = `
				<div style="
					display: flex;
					align-items: center;
					gap: 8px;
					font-weight: 600;
					color: #16a34a;
					font-size: 0.95rem;
					animation: fadeIn 0.3s ease;
				">
					<div style="
						width: 22px; height: 22px;
						border-radius: 50%;
						background-color: #16a34a;
						display: flex;
						align-items: center;
						justify-content: center;
						color: #fff;
						font-size: 14px;
						animation: pop 0.4s ease;
					">✔</div>
					Code validé !
				</div>
			`;
			area.appendChild(successDiv);
			area.scrollTop = area.scrollHeight;

			// ⏳ Attente avant d’afficher la licence
			setTimeout(() => {
				append(area, `
					🎉 Vérification réussie !<br><br>
					<strong>Numéro de licence :</strong> ${d.license}<br>
					(au nom de ${d.fullName})
				`, "bot");
				appendReturn(area);
			}, 1000);
		} else {
			// ❌ Animation "code invalide"
			const errorDiv = document.createElement("div");
			errorDiv.classList.add("assistant-msg", "bot");
			errorDiv.innerHTML = `
				<div style="
					display: flex;
					align-items: center;
					gap: 8px;
					font-weight: 600;
					color: #dc2626;
					font-size: 0.95rem;
					animation: fadeIn 0.3s ease;
				">
					<div style="
						width: 22px; height: 22px;
						border-radius: 50%;
						background-color: #dc2626;
						display: flex;
						align-items: center;
						justify-content: center;
						color: #fff;
						font-size: 14px;
						animation: pop 0.4s ease;
					">✖</div>
					Code invalide ou expiré.
				</div>
			`;
			area.appendChild(errorDiv);
			area.scrollTop = area.scrollHeight;
			appendReturn(area);
		}
	} catch (err) {
		console.error("Erreur /assistant/license/verify :", err);
		hideTyping(area);
		append(area, "⛔ Erreur de communication avec le serveur.", "bot");
		appendReturn(area);
	}
	break;

        }
      }
    }

    function append(c,t,s="bot"){const m=document.createElement("div");
      m.classList.add("assistant-msg",s);m.innerHTML=t;c.appendChild(m);
      c.scrollTop=c.scrollHeight;}
    function showTyping(c){const t=document.createElement("div");
      t.classList.add("assistant-msg","bot");t.id="eliosTyping";
      t.innerHTML="<div class='assistant-typing'><span></span><span></span><span></span></div>";
      c.appendChild(t);c.scrollTop=c.scrollHeight;}
    function hideTyping(c){const t=document.getElementById("eliosTyping");if(t)t.remove();}
    function appendReturn(c){
      if(document.getElementById("assistantReturnHome"))return;
      const b=document.createElement("div");b.classList.add("assistant-actions");
      b.innerHTML=`<button id="assistantReturnHome" class="assistant-btn"
        style="background:#e5e7eb;color:#111;font-weight:600;
        padding:6px 12px;border-radius:10px;font-size:.85rem;">🏠 Retour à l’accueil</button>`;
      c.appendChild(b);c.scrollTop=c.scrollHeight;
    }

    loadHomeMenu();
  });
}
