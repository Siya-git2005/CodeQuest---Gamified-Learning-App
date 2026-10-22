// ============================================================
// CodeQuest - Main JavaScript
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // === Sidebar Toggle (Mobile) ===
    const hamburger = document.getElementById('hamburger');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    if (hamburger && sidebar) {
        hamburger.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('visible');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            if (sidebar) sidebar.classList.remove('open');
            overlay.classList.remove('visible');
        });
    }

    // === Tab System ===
    const tabs = document.querySelectorAll('.lesson-tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.getAttribute('data-tab');

            // Remove active from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.lesson-tab-content').forEach(c => c.classList.remove('active'));

            // Activate clicked tab
            tab.classList.add('active');
            const content = document.getElementById('tab-' + target);
            if (content) {
                content.classList.add('active');
            }
        });
    });

    // === Hint Button ===
    const hintBtn = document.getElementById('hint-btn');
    const hintBox = document.getElementById('hint-box');

    if (hintBtn && hintBox) {
        hintBtn.addEventListener('click', () => {
            hintBox.classList.toggle('visible');
            if (hintBox.classList.contains('visible')) {
                hintBtn.innerHTML = '💡 Hide Hint';
                hintBox.style.animation = 'fadeIn 0.3s ease';
            } else {
                hintBtn.innerHTML = '💡 Show Hint';
            }
        });
    }

    // === Solution Button ===
    const solBtn = document.getElementById('solution-btn');
    const solBox = document.getElementById('solution-box');

    if (solBtn && solBox) {
        let confirmed = false;
        solBtn.addEventListener('click', () => {
            if (!confirmed) {
                if (confirm('Are you sure you want to see the solution? Try to solve it yourself first!')) {
                    confirmed = true;
                    solBox.classList.add('visible');
                    solBtn.innerHTML = '✅ Solution Shown';
                    solBtn.disabled = true;
                }
            }
        });
    }

    // === Copy Code Buttons ===
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const codeBlock = btn.parentElement.querySelector('pre') || btn.parentElement;
            const code = codeBlock.innerText.replace('Copy', '').trim();

            navigator.clipboard.writeText(code).then(() => {
                btn.textContent = '✓ Copied!';
                btn.style.color = '#43E97B';
                setTimeout(() => {
                    btn.textContent = 'Copy';
                    btn.style.color = '';
                }, 2000);
            }).catch(() => {
                // Fallback
                const ta = document.createElement('textarea');
                ta.value = code;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                btn.textContent = '✓ Copied!';
                setTimeout(() => btn.textContent = 'Copy', 2000);
            });
        });
    });

    // === Modal System ===
    document.querySelectorAll('[data-modal]').forEach(trigger => {
        trigger.addEventListener('click', () => {
            const modalId = trigger.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.add('open');
        });
    });

    document.querySelectorAll('.modal-close, .modal-overlay').forEach(el => {
        el.addEventListener('click', (e) => {
            if (e.target === el) {
                document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('open'));
            }
        });
    });

    // === Animate progress bars on load ===
    document.querySelectorAll('.progress-fill').forEach(bar => {
        const width = bar.getAttribute('data-width') || bar.style.width;
        bar.style.width = '0';
        setTimeout(() => {
            bar.style.width = width;
        }, 300);
    });

    // === Auto-dismiss alerts ===
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 400);
        }, 4000);
    });

    // === Active sidebar link ===
    const currentPath = window.location.pathname;
    document.querySelectorAll('.sidebar-nav ul li a').forEach(link => {
        if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href').replace('../', ''))) {
            link.classList.add('active');
        }
    });

    // === Code editor line numbers feel ===
    document.querySelectorAll('.code-editor').forEach(editor => {
        editor.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                e.preventDefault();
                const start = editor.selectionStart;
                const end = editor.selectionEnd;
                editor.value = editor.value.substring(0, start) + '    ' + editor.value.substring(end);
                editor.selectionStart = editor.selectionEnd = start + 4;
            }
        });
    });

    // === Unlock animation ===
    document.querySelectorAll('.level-card.unlocked').forEach((card, i) => {
        card.style.animationDelay = (i * 0.08) + 's';
        card.classList.add('fade-in');
    });

    // === Celebration when level completed ===
    if (document.getElementById('level-complete-message')) {
        showConfetti();
    }

});

// === Confetti Animation ===
function showConfetti() {
    const canvas = document.createElement('canvas');
    canvas.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999;';
    document.body.appendChild(canvas);
    const ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;

    const particles = [];
    const colors = ['#6C63FF', '#43E97B', '#FF6584', '#FFD700', '#00D4FF'];

    for (let i = 0; i < 120; i++) {
        particles.push({
            x: Math.random() * canvas.width,
            y: -20,
            color: colors[Math.floor(Math.random() * colors.length)],
            w: Math.random() * 10 + 5,
            h: Math.random() * 5 + 3,
            rotation: Math.random() * 360,
            speed: Math.random() * 3 + 2,
            rotSpeed: Math.random() * 5 - 2.5,
            xSpeed: Math.random() * 2 - 1,
        });
    }

    let frame = 0;
    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        particles.forEach(p => {
            p.y += p.speed;
            p.x += p.xSpeed;
            p.rotation += p.rotSpeed;
            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate(p.rotation * Math.PI / 180);
            ctx.fillStyle = p.color;
            ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
            ctx.restore();
        });
        frame++;
        if (frame < 150) requestAnimationFrame(animate);
        else canvas.remove();
    }
    animate();
}

// === XP Counter Animation ===
function animateCounter(element, end) {
    let start = 0;
    const duration = 1500;
    const step = end / (duration / 16);
    const timer = setInterval(() => {
        start += step;
        element.textContent = Math.min(Math.floor(start), end);
        if (start >= end) clearInterval(timer);
    }, 16);
}

// ============================================================
// CODEQUEST - MOVING NEW PLAYER TUTORIAL
// DASHBOARD EXACT VERSION
// ============================================================

document.addEventListener("DOMContentLoaded", function () {

    const assistant = document.getElementById("codequest-assistant");

    if (!assistant) return;

    // ------------------------------------------------------------
    // CSS
    // ------------------------------------------------------------
    const oldStyle = document.getElementById("cq-moving-style-v2");
    if (oldStyle) oldStyle.remove();

    const style = document.createElement("style");
    style.id = "cq-moving-style-v2";

    style.textContent = `
        #cq-moving-overlay {
            position: fixed;
            inset: 0;
            z-index: 999999;
            pointer-events: none;
        }

        #cq-moving-highlight {
            position: fixed;
            z-index: 999999;
            border: 3px solid #7c6cff;
            border-radius: 16px;
            background: rgba(124,108,255,0.10);
            box-shadow:
                0 0 0 9999px rgba(5,5,18,0.78),
                0 0 30px rgba(124,108,255,0.8);
            transition:
                left .45s ease,
                top .45s ease,
                width .45s ease,
                height .45s ease;
            pointer-events: none;
        }

        #cq-moving-character {
            position: fixed;
            width: 190px;
            height: 250px;
            z-index: 1000002;
            display: flex;
            justify-content: center;
            align-items: flex-end;
            transition:
                left .55s cubic-bezier(.22,1,.36,1),
                top .55s cubic-bezier(.22,1,.36,1),
                transform .35s ease;
            pointer-events: none;
        }

        #cq-moving-character img {
            width: 180px;
            max-height: 245px;
            object-fit: contain;
            filter: drop-shadow(0 10px 15px rgba(0,0,0,.35));
            animation: cqBabyFloat 2.2s ease-in-out infinite;
        }

        @keyframes cqBabyFloat {
            0%,100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-8px);
            }
        }

        #cq-moving-dialog {
            position: fixed;
            z-index: 1000003;
            width: min(390px, calc(100vw - 30px));
            transition:
                left .55s cubic-bezier(.22,1,.36,1),
                top .55s cubic-bezier(.22,1,.36,1);
            pointer-events: auto;
        }

        #cq-moving-bubble {
            background: white;
            color: #202030;
            border-radius: 20px;
            padding: 20px 22px;
            box-shadow: 0 15px 45px rgba(0,0,0,.35);
            border: 3px solid #7c6cff;
        }

        #cq-moving-title {
            font-size: 20px;
            font-weight: 800;
            color: #594fe0;
            margin-bottom: 8px;
        }

        #cq-moving-text {
            font-size: 15px;
            line-height: 1.55;
            color: #44445a;
        }

        #cq-moving-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-top: 12px;
        }

        #cq-moving-controls button {
            border: 0;
            border-radius: 10px;
            padding: 9px 15px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        #cq-moving-back {
            background: #eeeeF7;
            color: #44445a;
        }

        #cq-moving-next {
            background: #6558e8;
            color: white;
        }

        #cq-moving-progress {
            font-size: 13px;
            font-weight: 700;
            color: #77778b;
            white-space: nowrap;
        }

        #cq-moving-skip {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000005;
            border: 0;
            border-radius: 10px;
            padding: 10px 16px;
            background: rgba(255,255,255,.95);
            color: #44445a;
            font-weight: 700;
            cursor: pointer;
            pointer-events: auto;
            box-shadow: 0 5px 20px rgba(0,0,0,.2);
        }

        #cq-moving-arrow {
            position: fixed;
            z-index: 1000004;
            width: 0;
            height: 0;
            pointer-events: none;
            transform-origin: center center;
            transition:
                left .55s ease,
                top .55s ease,
                transform .55s ease;
        }

        #cq-moving-arrow::before {
            content: "";
            display: block;
            width: 48px;
            height: 6px;
            background: #ffcc33;
            border-radius: 10px;
            box-shadow: 0 0 12px rgba(255,204,51,.8);
        }

        #cq-moving-arrow::after {
            content: "";
            position: absolute;
            right: -7px;
            top: -7px;
            border-left: 14px solid #ffcc33;
            border-top: 10px solid transparent;
            border-bottom: 10px solid transparent;
        }

        @media(max-width:700px) {

            #cq-moving-character {
                width: 125px;
                height: 175px;
            }

            #cq-moving-character img {
                width: 120px;
                max-height: 170px;
            }

            #cq-moving-dialog {
                width: calc(100vw - 30px);
            }

            #cq-moving-bubble {
                padding: 15px 16px;
            }

            #cq-moving-title {
                font-size: 17px;
            }

            #cq-moving-text {
                font-size: 13px;
            }
        }
    `;

    document.head.appendChild(style);


    // ------------------------------------------------------------
    // PREVENT REPEATING
    // ------------------------------------------------------------

    const tutorialKey = "codequest_moving_tutorial_v2";

    if (localStorage.getItem(tutorialKey) === "true") {
        assistant.style.display = "none";
        return;
    }


    // ------------------------------------------------------------
    // GENDER
    // ------------------------------------------------------------

    const gender = assistant.getAttribute("data-gender") || "male";

    const characterImage =
        gender.toLowerCase() === "female"
            ? "/GamifiedLearningApp/assistant/codegirl.png"
            : "/GamifiedLearningApp/assistant/codeboy.png";

    const characterName =
        gender.toLowerCase() === "female"
            ? "CodeGirl"
            : "CodeBoy";


    // ------------------------------------------------------------
    // FIND ELEMENT BY TEXT
    // ------------------------------------------------------------

    function findHeading(text) {

        const elements = document.querySelectorAll("h1,h2,h3,h4");

        for (const el of elements) {

            if (
                el.textContent
                    .trim()
                    .toLowerCase()
                    .includes(text.toLowerCase())
            ) {
                return el;
            }
        }

        return null;
    }


    // ------------------------------------------------------------
    // TUTORIAL STEPS
    // ------------------------------------------------------------

    const steps = [

        {
            title: `👋 Hi! I'm ${characterName}!`,
            text:
                "Welcome to CodeQuest! 🎮 I'm going to give you a quick tour of your learning dashboard.",
            target: null
        },

        {
            title: "🏠 Your Dashboard",
            text:
                "This is your main dashboard. From here you can see your learning progress and continue your coding journey.",
            target: () => document.querySelector(".topbar")
        },

        {
            title: "⭐ Your Progress",
            text:
                "These cards show your completed levels, started languages, XP and total levels available.",
            target: () => document.querySelector(".stats-grid")
        },

        {
            title: "⚡ Earn XP",
            text:
                "Every time you learn and practice, you can earn XP. More XP means more progress in your CodeQuest journey.",
            target: () => document.querySelector(".xp-badge")
        },

        {
            title: "📚 Choose a Language",
            text:
                "Here you can choose the programming language you want to learn. Start with any language you like!",
            target: () => findHeading("Choose a Language")
        },

        {
            title: "💻 Start Learning",
            text:
                "These language cards take you to the different levels of your selected programming language.",
            target: () => document.querySelector(".lang-card")
        },

        {
            title: "🚀 Explore Your Levels",
            text:
                "Click a language card to open its levels. Complete lessons and challenges to make progress.",
            target: () => document.querySelector(".card-grid")
        },

        {
            title: "🎓 Your Certificates",
            text:
                "When you complete eligible learning paths, your certificates will appear here.",
            target: () =>
                findHeading("My Certificates") ||
                document.querySelector('a[href="my_certificates.php"]')
        },

        {
            title: "📜 View All Certificates",
            text:
                "You can open your certificate section to view your earned certificates and their details.",
            target: () =>
                document.querySelector('a[href="my_certificates.php"]')
        },

        {
            title: "🎉 You're Ready!",
            text:
                "That's it! Your CodeQuest journey starts now. Learn, practice, earn XP and become a coding champion! 🏆",
            target: null
        }

    ];


    // ------------------------------------------------------------
    // CREATE UI
    // ------------------------------------------------------------

    assistant.style.display = "block";

    assistant.innerHTML = `

        <div id="cq-moving-overlay">

            <div id="cq-moving-highlight"></div>

            <div id="cq-moving-arrow"></div>

            <button id="cq-moving-skip">
                Skip Tutorial ✕
            </button>

            <div id="cq-moving-character">
                <img
                    src="${characterImage}"
                    alt="${characterName}"
                >
            </div>

            <div id="cq-moving-dialog">

                <div id="cq-moving-bubble">

                    <div id="cq-moving-title"></div>

                    <div id="cq-moving-text"></div>

                    <div id="cq-moving-controls">

                        <button id="cq-moving-back">
                            ← Back
                        </button>

                        <div id="cq-moving-progress">
                            1 / 10
                        </div>

                        <button id="cq-moving-next">
                            Next →
                        </button>

                    </div>

                </div>

            </div>

        </div>
    `;


    const character = document.getElementById("cq-moving-character");
    const dialog = document.getElementById("cq-moving-dialog");
    const highlight = document.getElementById("cq-moving-highlight");
    const arrow = document.getElementById("cq-moving-arrow");

    const title = document.getElementById("cq-moving-title");
    const text = document.getElementById("cq-moving-text");

    const progress = document.getElementById("cq-moving-progress");

    const back = document.getElementById("cq-moving-back");
    const next = document.getElementById("cq-moving-next");
    const skip = document.getElementById("cq-moving-skip");


    let currentStep = 0;


    // ------------------------------------------------------------
    // POSITION CHARACTER
    // ------------------------------------------------------------

    function positionCharacter(target) {

        const vw = window.innerWidth;
        const vh = window.innerHeight;

        const charWidth = window.innerWidth <= 700 ? 125 : 190;
        const charHeight = window.innerWidth <= 700 ? 175 : 250;

        let charLeft;
        let charTop;

        let targetRect = null;

        if (target) {

            target.scrollIntoView({
                behavior: "smooth",
                block: "center",
                inline: "nearest"
            });

            targetRect = target.getBoundingClientRect();

            const targetCenterX =
                targetRect.left + targetRect.width / 2;

            const targetCenterY =
                targetRect.top + targetRect.height / 2;


            // Target is on left side
            if (targetCenterX < vw / 2) {

                charLeft = targetRect.right + 15;

            }

            // Target is on right side
            else {

                charLeft = targetRect.left - charWidth - 15;

            }


            // If there is not enough room,
            // put character below target

            if (
                charLeft < 10 ||
                charLeft + charWidth > vw - 10
            ) {

                charLeft =
                    targetCenterX - charWidth / 2;

                charTop =
                    targetRect.bottom + 15;

            } else {

                charTop =
                    targetCenterY - charHeight / 2;

            }

        }

        // Center position
        else {

            charLeft =
                (vw - charWidth) / 2;

            charTop =
                Math.max(
                    80,
                    (vh - charHeight) / 2 - 40
                );

        }


        // Keep character inside screen

        charLeft = Math.max(
            10,
            Math.min(
                charLeft,
                vw - charWidth - 10
            )
        );

        charTop = Math.max(
            60,
            Math.min(
                charTop,
                vh - charHeight - 10
            )
        );


        character.style.left = charLeft + "px";
        character.style.top = charTop + "px";


        // --------------------------------------------------------
        // Character direction
        // --------------------------------------------------------

        if (targetRect) {

            const targetCenterX =
                targetRect.left +
                targetRect.width / 2;

            const charCenterX =
                charLeft +
                charWidth / 2;

            if (targetCenterX < charCenterX) {

                character.style.transform =
                    "scaleX(-1)";

            } else {

                character.style.transform =
                    "scaleX(1)";

            }

        } else {

            character.style.transform =
                "scaleX(1)";
        }


        // --------------------------------------------------------
        // Dialog positioning
        // --------------------------------------------------------

        const dialogWidth =
            Math.min(
                390,
                vw - 30
            );

        let dialogLeft;

        if (targetRect) {

            const targetCenterX =
                targetRect.left +
                targetRect.width / 2;

            if (targetCenterX < vw / 2) {

                dialogLeft =
                    Math.min(
                        vw - dialogWidth - 15,
                        charLeft + charWidth + 10
                    );

            } else {

                dialogLeft =
                    Math.max(
                        15,
                        charLeft - dialogWidth - 10
                    );
            }

        } else {

            dialogLeft =
                (vw - dialogWidth) / 2;

        }


        let dialogTop;

        if (targetRect) {

            dialogTop =
                charTop + charHeight - 15;

        } else {

            dialogTop =
                Math.min(
                    vh - 210,
                    charTop + charHeight - 5
                );
        }


        dialogLeft = Math.max(
            15,
            Math.min(
                dialogLeft,
                vw - dialogWidth - 15
            )
        );

        dialogTop = Math.max(
            70,
            Math.min(
                dialogTop,
                vh - 180
            )
        );


        dialog.style.left =
            dialogLeft + "px";

        dialog.style.top =
            dialogTop + "px";


        // --------------------------------------------------------
        // Arrow
        // --------------------------------------------------------

        if (targetRect) {

            const targetX =
                targetRect.left +
                targetRect.width / 2;

            const targetY =
                targetRect.top +
                targetRect.height / 2;

            const charX =
                charLeft +
                charWidth / 2;

            const charY =
                charTop +
                charHeight / 2;

            const dx = targetX - charX;
            const dy = targetY - charY;

            const angle =
                Math.atan2(dy, dx) *
                180 /
                Math.PI;

            arrow.style.left =
                (charX + dx / 2 - 24) + "px";

            arrow.style.top =
                (charY + dy / 2 - 3) + "px";

            arrow.style.transform =
                `rotate(${angle}deg)`;

            arrow.style.display =
                "block";

        } else {

            arrow.style.display =
                "none";
        }

    }


    // ------------------------------------------------------------
    // SHOW STEP
    // ------------------------------------------------------------

    function showStep(index) {

        currentStep = index;

        const step =
            steps[currentStep];

        title.textContent =
            step.title;

        text.textContent =
            step.text;

        progress.textContent =
            `${currentStep + 1} / ${steps.length}`;


        back.style.visibility =
            currentStep === 0
                ? "hidden"
                : "visible";


        next.textContent =
            currentStep === steps.length - 1
                ? "Finish ✓"
                : "Next →";


        let target = null;

        try {

            if (typeof step.target === "function") {
                target = step.target();
            }

        } catch (e) {

            target = null;

        }


        // If certificate doesn't exist,
        // don't leave user on broken target

        if (
            (currentStep === 7 ||
             currentStep === 8) &&
            !target
        ) {

            target = null;
        }


        if (target) {

            highlight.style.display =
                "block";

            const rect =
                target.getBoundingClientRect();

            highlight.style.left =
                (rect.left - 8) + "px";

            highlight.style.top =
                (rect.top - 8) + "px";

            highlight.style.width =
                (rect.width + 16) + "px";

            highlight.style.height =
                (rect.height + 16) + "px";

        } else {

            highlight.style.display =
                "none";
        }


        positionCharacter(target);

    }


    // ------------------------------------------------------------
    // CLOSE
    // ------------------------------------------------------------

    function closeTutorial() {

        localStorage.setItem(
            tutorialKey,
            "true"
        );

        assistant.style.display =
            "none";
    }


    // ------------------------------------------------------------
    // BUTTONS
    // ------------------------------------------------------------

    next.addEventListener(
        "click",
        function () {

            if (
                currentStep >=
                steps.length - 1
            ) {

                closeTutorial();
                return;
            }

            showStep(
                currentStep + 1
            );
        }
    );


    back.addEventListener(
        "click",
        function () {

            if (currentStep > 0) {

                showStep(
                    currentStep - 1
                );

            }

        }
    );


    skip.addEventListener(
        "click",
        closeTutorial
    );


    // ------------------------------------------------------------
    // RESIZE
    // ------------------------------------------------------------

    window.addEventListener(
        "resize",
        function () {

            const step =
                steps[currentStep];

            let target = null;

            try {

                if (
                    typeof step.target ===
                    "function"
                ) {

                    target =
                        step.target();
                }

            } catch (e) {}

            if (target) {

                const rect =
                    target.getBoundingClientRect();

                highlight.style.left =
                    (rect.left - 8) + "px";

                highlight.style.top =
                    (rect.top - 8) + "px";

                highlight.style.width =
                    (rect.width + 16) + "px";

                highlight.style.height =
                    (rect.height + 16) + "px";
            }

            positionCharacter(target);
        }
    );


    // ------------------------------------------------------------
    // START
    // ------------------------------------------------------------

    showStep(0);

});