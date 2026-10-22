// ============================================================
// CODEQUEST - MAIN.JS
// Dashboard + New Player Tutorial
// FINAL NEW USER VERSION
// ============================================================

document.addEventListener("DOMContentLoaded", function () {

    // ============================================================
    // SIDEBAR
    // ============================================================

    const menuToggle =
        document.getElementById("menu-toggle");

    const sidebar =
        document.getElementById("sidebar");

    const sidebarOverlay =
        document.getElementById("sidebar-overlay");


    function openSidebar() {

        if (sidebar) {
            sidebar.classList.add("open");
        }

        if (sidebarOverlay) {
            sidebarOverlay.classList.add("visible");
        }
    }


    function closeSidebar() {

        if (sidebar) {
            sidebar.classList.remove("open");
        }

        if (sidebarOverlay) {
            sidebarOverlay.classList.remove("visible");
        }
    }


    if (menuToggle) {

        menuToggle.addEventListener(
            "click",
            function () {

                if (
                    sidebar &&
                    sidebar.classList.contains("open")
                ) {

                    closeSidebar();

                } else {

                    openSidebar();
                }

            }
        );
    }


    if (sidebarOverlay) {

        sidebarOverlay.addEventListener(
            "click",
            closeSidebar
        );
    }


    // ============================================================
    // CLOSE SIDEBAR ON MOBILE
    // ============================================================

    if (sidebar) {

        const sidebarLinks =
            sidebar.querySelectorAll("a");


        sidebarLinks.forEach(
            function (link) {

                link.addEventListener(
                    "click",
                    function () {

                        if (
                            window.innerWidth <= 900
                        ) {

                            closeSidebar();
                        }

                    }
                );

            }
        );
    }


    // ============================================================
    // CODEQUEST NEW PLAYER TUTORIAL
    // ============================================================

    const assistant =
        document.getElementById(
            "codequest-assistant"
        );


    if (!assistant) {
        return;
    }


    // ============================================================
    // CHECK DATABASE VALUE
    // ============================================================

    const tutorialSeen =
        assistant.getAttribute(
            "data-tutorial-seen"
        ) || "0";


    // Existing user:
    // tutorial_seen = 1
    //
    // Do NOT show tutorial.

    if (tutorialSeen === "1") {

        assistant.style.display =
            "none";

        return;
    }


    // ============================================================
    // USER ID
    // ============================================================

    const userId =
        assistant.getAttribute(
            "data-user-id"
        ) || "0";


    // ============================================================
    // GENDER
    // ============================================================

    const gender =
        assistant.getAttribute(
            "data-gender"
        ) || "male";


    const characterImage =
        gender.toLowerCase() === "female"
            ? "/GamifiedLearningApp/assistant/codegirl.png"
            : "/GamifiedLearningApp/assistant/codeboy.png";


    const characterName =
        gender.toLowerCase() === "female"
            ? "CodeGirl"
            : "CodeBoy";


    // ============================================================
    // TUTORIAL CSS
    // ============================================================

    const oldStyle =
        document.getElementById(
            "codequest-tutorial-style-final"
        );


    if (oldStyle) {
        oldStyle.remove();
    }


    const style =
        document.createElement("style");


    style.id =
        "codequest-tutorial-style-final";


    style.textContent = `

        /* ========================================================
           MAIN TUTORIAL
           ======================================================== */

        #cq-tutorial {

            position: fixed;

            inset: 0;

            z-index: 999999;

            pointer-events: none;
        }


        /* ========================================================
           DARK OVERLAY
           ======================================================== */

        #cq-tutorial-dark {

            position: fixed;

            inset: 0;

            background:
                rgba(0, 0, 0, 0.70);

            z-index: 999998;

            pointer-events: none;
        }


        /* ========================================================
           HIGHLIGHT BOX
           ======================================================== */

        #cq-tutorial-highlight {

            position: fixed;

            z-index: 999999;

            border:
                3px solid #7567f8;

            border-radius:
                12px;

            background:
                rgba(
                    117,
                    103,
                    248,
                    0.10
                );

            box-shadow:
                0 0 0 4px
                rgba(
                    117,
                    103,
                    248,
                    0.16
                ),

                0 0 18px
                rgba(
                    117,
                    103,
                    248,
                    0.40
                );

            transition:
                left .35s ease,
                top .35s ease,
                width .35s ease,
                height .35s ease;

            pointer-events: none;
        }


        /* ========================================================
           BABY CHARACTER
           ======================================================== */

        #cq-tutorial-character {

            position: fixed;

            width: 170px;

            height: 230px;

            z-index: 1000001;

            display:
                flex;

            align-items:
                flex-end;

            justify-content:
                center;

            pointer-events:
                none;

            transition:
                left .45s ease,
                top .45s ease,
                transform .3s ease;
        }


        #cq-tutorial-character img {

            width: 165px;

            height: 225px;

            object-fit:
                contain;

            filter:
                drop-shadow(
                    0 10px 15px
                    rgba(0,0,0,.35)
                );

            animation:
                cqBabyFloat 2s
                ease-in-out
                infinite;
        }


        @keyframes cqBabyFloat {

            0% {
                transform:
                    translateY(0);
            }

            50% {
                transform:
                    translateY(-7px);
            }

            100% {
                transform:
                    translateY(0);
            }
        }


        /* ========================================================
           SPEECH DIALOG
           ======================================================== */

        #cq-tutorial-dialog {

            position: fixed;

            width: 360px;

            z-index: 1000002;

            pointer-events:
                auto;

            transition:
                left .45s ease,
                top .45s ease;
        }


        #cq-tutorial-bubble {

            background:
                white;

            color:
                #333;

            border-radius:
                18px;

            padding:
                18px 20px;

            border:
                3px solid #7567f8;

            box-shadow:
                0 12px 40px
                rgba(0,0,0,.35);
        }


        #cq-tutorial-title {

            font-size:
                19px;

            font-weight:
                800;

            color:
                #5c50dc;

            margin-bottom:
                7px;
        }


        #cq-tutorial-text {

            font-size:
                14px;

            line-height:
                1.55;

            color:
                #44445a;
        }


        /* ========================================================
           BUTTONS
           ======================================================== */

        #cq-tutorial-controls {

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                8px;

            margin-top:
                14px;
        }


        #cq-tutorial-controls button {

            border:
                none;

            border-radius:
                9px;

            padding:
                9px 14px;

            font-size:
                13px;

            font-weight:
                700;

            cursor:
                pointer;
        }


        #cq-tutorial-back {

            background:
                #eeeeF5;

            color:
                #44445a;
        }


        #cq-tutorial-next {

            background:
                #6558e8;

            color:
                white;
        }


        #cq-tutorial-progress {

            color:
                #77778a;

            font-size:
                13px;

            font-weight:
                700;

            white-space:
                nowrap;
        }


        /* ========================================================
           SKIP BUTTON
           ======================================================== */

        #cq-tutorial-skip {

            position:
                fixed;

            top:
                18px;

            right:
                18px;

            z-index:
                1000005;

            border:
                none;

            border-radius:
                9px;

            padding:
                9px 14px;

            background:
                white;

            color:
                #44445a;

            font-weight:
                700;

            cursor:
                pointer;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.25);

            pointer-events:
                auto;
        }


        /* ========================================================
           MOBILE
           ======================================================== */

        @media (max-width:700px) {

            #cq-tutorial-character {

                width:
                    115px;

                height:
                    165px;
            }


            #cq-tutorial-character img {

                width:
                    110px;

                height:
                    160px;
            }


            #cq-tutorial-dialog {

                width:
                    calc(100vw - 30px);
            }


            #cq-tutorial-bubble {

                padding:
                    14px 16px;
            }


            #cq-tutorial-title {

                font-size:
                    17px;
            }


            #cq-tutorial-text {

                font-size:
                    13px;
            }

        }

    `;


    document.head.appendChild(style);


    // ============================================================
    // FIND HEADING
    // ============================================================

    function findHeading(text) {

        const headings =
            document.querySelectorAll(
                "h1, h2, h3, h4"
            );


        for (
            const heading of headings
        ) {

            const value =
                heading.textContent
                    .trim()
                    .toLowerCase();


            if (
                value.includes(
                    text.toLowerCase()
                )
            ) {

                return heading;
            }
        }


        return null;
    }


    // ============================================================
    // TUTORIAL STEPS
    // ============================================================

    const steps = [

        {
            title:
                `👋 Hi! I'm ${characterName}!`,

            text:
                "Welcome to CodeQuest! 🎮 I'll quickly show you around your learning dashboard.",

            target:
                null
        },


        {
            title:
                "🏠 Welcome to Your Dashboard",

            text:
                "This is your main dashboard. Here you can see your coding progress and continue your learning journey.",

            target:
                () =>
                    document.querySelector(
                        ".topbar"
                    )
        },


        {
            title:
                "📊 Your Progress",

            text:
                "These cards show your completed levels, started languages, XP and total available levels.",

            target:
                () =>
                    document.querySelector(
                        ".stats-grid"
                    )
        },


        {
            title:
                "⚡ Your XP",

            text:
                "You earn XP as you learn and practice. Your total XP is shown here.",

            target:
                () =>
                    document.querySelector(
                        ".xp-badge"
                    )
        },


        {
            title:
                "📚 Choose a Language",

            text:
                "Here you can choose the programming language you want to learn.",

            target:
                () =>
                    findHeading(
                        "Choose a Language"
                    )
        },


        {
            title:
                "💻 Start Learning",

            text:
                "Each language has its own learning path. Click a language card to open its levels.",

            target:
                () =>
                    document.querySelector(
                        ".lang-card"
                    )
        },


        {
            title:
                "🚀 Your Learning Paths",

            text:
                "All available programming languages are displayed here. Choose any language and start learning.",

            target:
                () =>
                    document.querySelector(
                        ".card-grid"
                    )
        },


        {
            title:
                "🎓 Your Certificates",

            text:
                "When you earn certificates, they will appear in this section.",

            target:
                () =>
                    findHeading(
                        "My Certificates"
                    )
        },


        {
            title:
                "📜 View Your Certificates",

            text:
                "You can open your certificate section to view your earned certificates and details.",

            target:
                () =>
                    document.querySelector(
                        'a[href="my_certificates.php"]'
                    )
        },


        {
            title:
                "🎉 You're Ready!",

            text:
                "That's it! Your CodeQuest journey starts now. Learn, practice, earn XP and become a coding champion! 🏆",

            target:
                null
        }

    ];


    // ============================================================
    // CREATE TUTORIAL
    // ============================================================

    assistant.style.display =
        "block";


    assistant.innerHTML = `

        <div id="cq-tutorial">

            <div id="cq-tutorial-dark"></div>

            <div id="cq-tutorial-highlight"></div>


            <button id="cq-tutorial-skip">
                Skip Tutorial ✕
            </button>


            <div id="cq-tutorial-character">

                <img
                    src="${characterImage}"
                    alt="${characterName}"
                >

            </div>


            <div id="cq-tutorial-dialog">

                <div id="cq-tutorial-bubble">

                    <div id="cq-tutorial-title"></div>

                    <div id="cq-tutorial-text"></div>


                    <div id="cq-tutorial-controls">

                        <button id="cq-tutorial-back">
                            ← Back
                        </button>


                        <div id="cq-tutorial-progress">
                            1 / 10
                        </div>


                        <button id="cq-tutorial-next">
                            Next →
                        </button>

                    </div>

                </div>

            </div>

        </div>

    `;


    // ============================================================
    // ELEMENTS
    // ============================================================

    const highlight =
        document.getElementById(
            "cq-tutorial-highlight"
        );


    const character =
        document.getElementById(
            "cq-tutorial-character"
        );


    const dialog =
        document.getElementById(
            "cq-tutorial-dialog"
        );


    const title =
        document.getElementById(
            "cq-tutorial-title"
        );


    const text =
        document.getElementById(
            "cq-tutorial-text"
        );


    const progress =
        document.getElementById(
            "cq-tutorial-progress"
        );


    const back =
        document.getElementById(
            "cq-tutorial-back"
        );


    const next =
        document.getElementById(
            "cq-tutorial-next"
        );


    const skip =
        document.getElementById(
            "cq-tutorial-skip"
        );


    let currentStep = 0;


    // ============================================================
    // GET TARGET
    // ============================================================

    function getTarget(step) {

        if (
            !step ||
            typeof step.target !== "function"
        ) {

            return null;
        }


        try {

            return step.target();

        } catch (error) {

            return null;
        }
    }


    // ============================================================
    // UPDATE HIGHLIGHT
    // ============================================================

    function updateHighlight(target) {

        if (!target) {

            highlight.style.display =
                "none";

            return;
        }


        const rect =
            target.getBoundingClientRect();


        highlight.style.display =
            "block";


        highlight.style.left =
            Math.max(
                3,
                rect.left - 6
            ) + "px";


        highlight.style.top =
            Math.max(
                3,
                rect.top - 6
            ) + "px";


        highlight.style.width =
            Math.max(
                20,
                rect.width + 12
            ) + "px";


        highlight.style.height =
            Math.max(
                20,
                rect.height + 12
            ) + "px";
    }


    // ============================================================
    // POSITION CHARACTER + DIALOG
    // ============================================================

    function positionTutorial(target) {

        const vw =
            window.innerWidth;


        const vh =
            window.innerHeight;


        const mobile =
            vw <= 700;


        const charWidth =
            mobile ? 115 : 170;


        const charHeight =
            mobile ? 165 : 230;


        let charLeft;
        let charTop;


        // --------------------------------------------------------
        // CENTER POSITION
        // --------------------------------------------------------

        if (!target) {

            charLeft =
                (vw - charWidth) / 2;


            charTop =
                Math.max(
                    70,
                    (vh - charHeight) / 2 - 100
                );
        }


        // --------------------------------------------------------
        // TARGET POSITION
        // --------------------------------------------------------

        else {

            const rect =
                target.getBoundingClientRect();


            const targetCenterX =
                rect.left +
                rect.width / 2;


            const targetCenterY =
                rect.top +
                rect.height / 2;


            // Target left side:
            // character goes to right

            if (
                targetCenterX <
                vw / 2
            ) {

                charLeft =
                    rect.right + 18;

            }

            // Target right side:
            // character goes to left

            else {

                charLeft =
                    rect.left -
                    charWidth -
                    18;
            }


            charTop =
                targetCenterY -
                charHeight / 2;


            // ----------------------------------------------------
            // If no horizontal space,
            // put character below target
            // ----------------------------------------------------

            if (
                charLeft < 10 ||
                charLeft +
                    charWidth >
                    vw - 10
            ) {

                charLeft =
                    targetCenterX -
                    charWidth / 2;


                charTop =
                    rect.bottom + 18;
            }


            // Keep character inside screen

            charLeft =
                Math.max(
                    10,
                    Math.min(
                        charLeft,
                        vw -
                            charWidth -
                            10
                    )
                );


            charTop =
                Math.max(
                    65,
                    Math.min(
                        charTop,
                        vh -
                            charHeight -
                            10
                    )
                );
        }


        // --------------------------------------------------------
        // CHARACTER POSITION
        // --------------------------------------------------------

        character.style.left =
            charLeft + "px";


        character.style.top =
            charTop + "px";


        // --------------------------------------------------------
        // CHARACTER FACE DIRECTION
        // --------------------------------------------------------

        if (target) {

            const rect =
                target.getBoundingClientRect();


            const targetCenterX =
                rect.left +
                rect.width / 2;


            const characterCenterX =
                charLeft +
                charWidth / 2;


            if (
                targetCenterX <
                characterCenterX
            ) {

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
        // DIALOG
        // --------------------------------------------------------

        const dialogWidth =
            Math.min(
                360,
                vw - 30
            );


        let dialogLeft;


        if (target) {

            const rect =
                target.getBoundingClientRect();


            const targetCenterX =
                rect.left +
                rect.width / 2;


            if (
                targetCenterX <
                vw / 2
            ) {

                dialogLeft =
                    charLeft +
                    charWidth +
                    10;

            } else {

                dialogLeft =
                    charLeft -
                    dialogWidth -
                    10;
            }

        } else {

            dialogLeft =
                (vw -
                    dialogWidth) /
                2;
        }


        dialogLeft =
            Math.max(
                15,
                Math.min(
                    dialogLeft,
                    vw -
                        dialogWidth -
                        15
                )
            );


        let dialogTop;


        if (target) {

            dialogTop =
                charTop +
                charHeight -
                20;

        } else {

            dialogTop =
                charTop +
                charHeight -
                5;
        }


        dialogTop =
            Math.max(
                65,
                Math.min(
                    dialogTop,
                    vh - 190
                )
            );


        dialog.style.left =
            dialogLeft + "px";


        dialog.style.top =
            dialogTop + "px";
    }


    // ============================================================
    // SHOW STEP
    // ============================================================

    function showStep(index) {

        currentStep =
            index;


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
            currentStep ===
            steps.length - 1
                ? "Finish ✓"
                : "Next →";


        const target =
            getTarget(step);


        if (target) {

            const rect =
                target.getBoundingClientRect();


            const isVisible =
                rect.top >= 70 &&
                rect.bottom <=
                    window.innerHeight - 30;


            if (!isVisible) {

                target.scrollIntoView({
                    behavior: "smooth",
                    block: "center",
                    inline: "nearest"
                });


                setTimeout(
                    function () {

                        updateHighlight(
                            target
                        );

                        positionTutorial(
                            target
                        );

                    },
                    400
                );

            } else {

                updateHighlight(
                    target
                );

                positionTutorial(
                    target
                );
            }

        } else {

            highlight.style.display =
                "none";


            positionTutorial(
                null
            );
        }
    }


    // ============================================================
    // MARK TUTORIAL AS SEEN IN DATABASE
    // ============================================================

    function markTutorialSeen() {

        if (!userId || userId === "0") {
            return;
        }


        fetch(
            "/GamifiedLearningApp/mark_tutorial_seen.php",
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/x-www-form-urlencoded"
                },

                body:
                    "user_id=" +
                    encodeURIComponent(userId)
            }
        )
        .then(
            function (response) {

                return response.text();
            }
        )
        .then(
            function (result) {

                console.log(
                    "Tutorial status:",
                    result
                );

            }
        )
        .catch(
            function (error) {

                console.error(
                    "Tutorial update failed:",
                    error
                );
            }
        );
    }


    // ============================================================
    // FINISH TUTORIAL
    // ============================================================

    function finishTutorial() {

        // Save in database
        markTutorialSeen();


        assistant.style.display =
            "none";
    }


    // ============================================================
    // NEXT
    // ============================================================

    next.addEventListener(
        "click",
        function () {

            if (
                currentStep >=
                steps.length - 1
            ) {

                finishTutorial();

                return;
            }


            showStep(
                currentStep + 1
            );
        }
    );


    // ============================================================
    // BACK
    // ============================================================

    back.addEventListener(
        "click",
        function () {

            if (
                currentStep > 0
            ) {

                showStep(
                    currentStep - 1
                );
            }
        }
    );


    // ============================================================
    // SKIP
    // ============================================================

    skip.addEventListener(
        "click",
        function () {

            finishTutorial();

        }
    );


    // ============================================================
    // WINDOW RESIZE
    // ============================================================

    window.addEventListener(
        "resize",
        function () {

            const step =
                steps[currentStep];


            const target =
                getTarget(step);


            if (target) {

                updateHighlight(
                    target
                );

                positionTutorial(
                    target
                );

            } else {

                positionTutorial(
                    null
                );
            }
        }
    );


    // ============================================================
    // START TUTORIAL
    // ============================================================

    showStep(0);

});