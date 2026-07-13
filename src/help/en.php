<?php
// Help center — English. Same structure/anchors as es.php (los ids no se traducen).
return [
    'title' => 'Help center',
    'intro' => 'Every screen, button and setting in Taninzu explained step by step. Use the search box or the index on the left.',
    'sections' => [

        ['id' => 'primeros-pasos', 'icon' => 'play', 'title' => 'Getting started', 'topics' => [
            ['id' => 'que-es', 'title' => 'What is Taninzu?', 'body' => '
<p>Taninzu is a platform to run Jiu-Jitsu tournaments end to end: online registration, bracket building, a scoreboard with timer for the control table, screens to project on the mat, PDF certificates sent by email and competitor rankings.</p>
<p>There are three kinds of users: the <b>organizer</b> (creates and manages tournaments), the <b>tournament staff</b> (referees and control table, invited by the organizer) and the <b>competitor</b> (registers through a link and follows their matches from their panel).</p>'],
            ['id' => 'crear-cuenta', 'title' => 'Creating an account', 'body' => '
<ol>
<li>Click <b class="hb">Create account</b> at the top right.</li>
<li>Fill in your name, email and a password of at least 6 characters.</li>
<li>You will receive an email with a button to verify your address — you cannot log in until you verify it.</li>
</ol>
<p>If you register for a tournament through its public link and you do not have an account yet, you can create one right in the registration form by setting a password: confirming the registration email also verifies the account.</p>'],
            ['id' => 'idioma-tema', 'title' => 'Changing language and theme (light/dark)', 'body' => '
<p>At the top right there are two controls:</p>
<ul>
<li>The <b>language dropdown</b> (Español / English / Português). On your first visit the browser language is detected; if you change it manually, your choice is remembered.</li>
<li>The <b class="hb">◐</b> button toggles between light and dark theme. Projection screens (scoreboard and brackets) always render dark, designed for projectors.</li>
</ul>'],
        ]],

        ['id' => 'mi-panel', 'icon' => 'home', 'title' => 'My panel', 'topics' => [
            ['id' => 'mis-torneos', 'title' => 'My tournaments: what each button does', 'body' => '
<p>The table lists the tournaments you created and the ones where you are staff (referee/control table). Each row has:</p>
<ul>
<li><b class="hb">▶ Go to tournament</b>: opens the operation screen (the Operation tab) — your command center on event day.</li>
<li><b class="hb">⚙</b>: opens the tournament Settings (details, staff, running orders, durations, etc.).</li>
<li><b class="hb">⤨</b> (clone): creates a new tournament copying structure and settings, without registrants or brackets. Only visible to the owner or an admin.</li>
<li><b class="hb">🗑</b> (delete): removes the tournament forever — it asks you to type the exact name to confirm. Owner or admin only.</li>
</ul>
<p>Each tournament shows a status badge: <b>Draft</b>, <b>Registration open</b>, <b>Running</b> or <b>Finished</b>.</p>'],
            ['id' => 'mis-inscripciones', 'title' => 'My registrations (competitor)', 'body' => '
<p>Below your tournaments you will find the tournaments where you are registered as a competitor. For each one you see:</p>
<ul>
<li>Your category (age, belt and weight) and whether your registration is <b>Verified</b> or <b>Pending</b> (check your email if it stays pending).</li>
<li><b>Next opponent</b>: who you fight next and in which round.</li>
<li>The list of your matches with result (You won / You lost) and score.</li>
<li><b class="hb">Your position in the bracket</b>: opens the full bracket of your division. If you also compete in the Absolute, a second button appears for that bracket.</li>
</ul>'],
        ]],

        ['id' => 'crear-torneo', 'icon' => 'trophy', 'title' => 'Creating a tournament', 'topics' => [
            ['id' => 'datos-basicos', 'title' => 'Basic tournament details', 'body' => '
<ol>
<li><b>Tournament name</b>: as it will appear on every screen and certificate.</li>
<li><b>Type</b>: <b>Internal</b> (a single academy — created automatically with the name and logo you upload) or <b>Open</b> (multiple academies, added later in the Academies tab).</li>
<li><b>Logo</b>: shown in the tournament header, projected screens and certificates.</li>
<li><b>Event date</b>: when that date arrives, the tournament switches automatically from "Registration open" to "Running".</li>
<li><b>Participant limit</b>: once reached, registration closes automatically.</li>
<li><b>Default match duration</b>: only used by special categories; regular divisions use the per-belt/per-category duration (see below).</li>
</ol>'],
            ['id' => 'disciplina', 'title' => 'Discipline: Gi or NoGi', 'body' => '
<p><b>Gi (with kimono)</b>: divisions are built by exact belt (White, Blue, Purple, Brown, Black), as usual.</p>
<p><b>NoGi (without kimono)</b>: kids and juveniles are grouped only by age and weight (belt does not matter); adults and masters are grouped by <b>tier</b>: Amateur, Semi Pro or Pro. Which belt falls in each tier is configurable — by default White and Blue = Amateur, Purple = Semi Pro, Brown and Black = Pro.</p>
<p>When you pick NoGi, the form shows the belt→tier mapping selectors and the running order switches to the 4 NoGi groups.</p>'],
            ['id' => 'ordenes-crear', 'title' => 'Running order, age order and weight order', 'body' => '
<p>These three lists are reordered by <b>dragging</b> the items (the number on the left is the position):</p>
<ul>
<li><b>Running order</b>: the order groups run during the event. Gi: kids/juveniles first, then belts (default black → white). NoGi: kids/juveniles, Amateur, Semi Pro and Pro last.</li>
<li><b>Age order</b>: within each group, the order of Adult, Master 1, Master 2, etc.</li>
<li><b>Weight order</b>: within each group and age, the order of weights (Rooster, Light Feather... Open Class).</li>
</ul>
<p>Initial values come from the site-wide settings; changing them here makes them this tournament\'s own order. Everything can be changed later from the tournament Settings, even mid-event — lists reorder instantly.</p>'],
            ['id' => 'duraciones-edades', 'title' => 'Match duration and age cutoffs', 'body' => '
<p><b>Match duration</b>: minutes per match by group. In Gi it is per belt (with a single value for kids/juveniles); in NoGi it is per category (Kids/Juveniles, Amateur, Semi Pro, Pro).</p>
<p><b>Ages</b>: up to which age (on Dec 31) someone counts as Kids and up to which as Juvenile; Adult starts after that. Only affects new registrations.</p>'],
        ]],

        ['id' => 'desarrollo', 'icon' => 'timer', 'title' => 'Operation tab (running the tournament)', 'topics' => [
            ['id' => 'desarrollo-resumen', 'title' => 'What the screen shows', 'body' => '
<p>This is the command center on tournament day. At the top: 4 cards with verified participants, matches (played / total and pending), completed divisions and the date.</p>
<ul>
<li><b>Live now</b>: matches running right now, with direct access to the operator and the scoreboard.</li>
<li><b>Next fights</b>: the next 8 matches ready to run, in the configured running order. Each shows the category, both competitors, the <b class="hb">⏱ Operator</b> button (opens the control table) and <b class="hb">🖵</b> (opens the projectable scoreboard).</li>
<li><b>Divisions</b>: every division as stacked cards following the same running order, with number of competitors, status (Pending / fights left / Done) and shortcuts to the bracket and the projector view. Finished divisions drop to the bottom.</li>
</ul>'],
        ]],

        ['id' => 'academias', 'icon' => 'flag', 'title' => 'Academies tab', 'topics' => [
            ['id' => 'academias-uso', 'title' => 'Adding academies and professors', 'body' => '
<ol>
<li>Type the academy name and click <b class="hb">Add academy</b>. You can upload a logo for it.</li>
<li>For each academy you can add <b>professors / branches</b> with <b class="hb">Add professor</b>.</li>
</ol>
<p>When competitors register, they pick their academy and professor from these lists. In an <b>Internal</b> tournament the organizing academy is created automatically. The Dashboard medal table is grouped by academy.</p>'],
        ]],

        ['id' => 'inscriptos', 'icon' => 'clipboard', 'title' => 'Registrations tab', 'topics' => [
            ['id' => 'inscriptos-tabla', 'title' => 'Reading the registrations table', 'body' => '
<p>Each row shows photo (if uploaded), name, email, gender, category, age, weight, academy and status. The list follows the tournament\'s running order.</p>
<ul>
<li>In <b>Gi</b> tournaments the category column shows the real belt with its color chip.</li>
<li>In <b>NoGi</b> tournaments it shows the category with its colored badge: <b>Kids and juveniles</b> (yellow), <b>Amateur</b> (white), <b>Semi Pro</b> (purple) or <b>Pro</b> (black).</li>
<li>If the weight column says <b>Absolute</b> (gold badge), the competitor signed up only for the absolute. If it shows the weight <b>and</b> the badge, they compete in both brackets.</li>
<li>Status <b>Verified</b> = email confirmed. <b>Pending</b> = not yet; pending registrants are not included in divisions.</li>
</ul>'],
            ['id' => 'inscriptos-acciones', 'title' => 'Verifying, editing and deleting registrants', 'body' => '
<ul>
<li><b class="hb">✓</b> (pending only): verifies the registration manually, without waiting for the email — useful when the email does not arrive.</li>
<li><b class="hb">✎</b>: opens the registrant editor (see next topic).</li>
<li><b class="hb">✕</b>: deletes the registration (asks for confirmation).</li>
</ul>'],
            ['id' => 'editar-inscripto', 'title' => 'Editing a registrant / moving them to another category', 'body' => '
<p>Through <b class="hb">✎</b> you can fix any detail: name, birthdate, weight, photo, academy... and also <b>move them to another category</b> (belt, age or weight, no restrictions) — that is how you merge a competitor left without opponents in their category.</p>
<p>You can also change what they compete in: <b>Category</b>, <b>Absolute</b> or <b>Category and Absolute</b>. Note: kids, juveniles, white belts (Gi) and Amateur tier (NoGi) cannot enter the Absolute — if a change would make them ineligible, the system silently sets them back to "Category" and tells you.</p>
<p><b>Important</b>: the change does not rearrange already generated divisions — after editing, click <b class="hb">Generate divisions</b> again in the Divisions & brackets tab.</p>'],
        ]],

        ['id' => 'divisiones', 'icon' => 'bracket', 'title' => 'Divisions & brackets tab', 'topics' => [
            ['id' => 'generar-divisiones', 'title' => 'Generate divisions', 'body' => '
<p>The <b class="hb">Generate divisions</b> button automatically creates one division per combination of gender + category + age + weight that has <b>verified</b> registrants. It is safe to click repeatedly: it only adds what is missing, it never deletes or duplicates.</p>
<p>When to click it again: after verifying new registrants, after editing someone\'s category, or after changing the NoGi tier mapping.</p>'],
            ['id' => 'categoria-especial', 'title' => 'Special categories', 'body' => '
<p>A special category is a bracket built entirely at your discretion, with no belt, weight or age restrictions (for example "Exhibition" or "Invited absolute").</p>
<ol>
<li>Type the name, pick the gender and click <b class="hb">+ Create</b>.</li>
<li>Open its <b class="hb">Bracket</b> and add registrants one by one with the <b class="hb">+ Add</b> selector (you can mix belts and ages freely).</li>
<li>Generate the bracket as usual (manual or random seeding).</li>
</ol>'],
            ['id' => 'divisiones-tabla', 'title' => 'The divisions table and its buttons', 'body' => '
<p>Each row shows gender, category, number of competitors, match duration and status (<b>Pending</b> = no bracket, <b>Bracket</b> generated, <b>Done</b>). Buttons:</p>
<ul>
<li><b class="hb">Bracket</b>: opens that division\'s management (build/regenerate the bracket, change duration).</li>
<li><b class="hb">🖵</b>: opens the bracket\'s projector view (public screen).</li>
<li><b class="hb">✕</b>: deletes the division along with its bracket and matches — registrants are untouched. If it was an auto-generated division and its registrants are still there, "Generate divisions" recreates it.</li>
</ul>'],
        ]],

        ['id' => 'armar-llave', 'icon' => 'bracket', 'title' => 'Building and reading a bracket', 'topics' => [
            ['id' => 'siembra', 'title' => 'Seeding: manual or random', 'body' => '
<ol>
<li>Under <b>Competitors</b>, order the participants with the dropdowns: the position defines who crosses whom (1 vs 2, 3 vs 4...).</li>
<li>Click <b class="hb">Save bracket</b> to generate with that order, or <b class="hb">⤨ Random</b> to draw it.</li>
<li>If the bracket already existed, the button reads <b class="hb">Regenerate bracket</b>: it wipes it and builds it again (results already entered for that division are lost).</li>
</ol>
<p>If the count is not a power of 2, the system distributes <b>byes</b> (automatic passes) following standard seeding. With 4 or more competitors a <b>third-place</b> match between the semifinal losers is created too.</p>'],
            ['id' => 'leer-llave', 'title' => 'How to read the bracket', 'body' => '
<ul>
<li>Each column is a round (Round 1, Semifinal, Final); lines connect each match to the next one.</li>
<li>The winner of each match is highlighted and carries the gold cup 🏆; in the Final, the loser carries the grey cup (2nd place).</li>
<li>"TBD" = that slot fills in when the previous match finishes.</li>
<li>Below each match you see the method (By points, Submission...) or the <b class="hb">⏱ Operator</b> link if it is pending.</li>
<li>When the division finishes, the podium (gold, silver, bronze) appears to the right of the bracket.</li>
</ul>'],
            ['id' => 'duracion-division', 'title' => 'Changing one division\'s duration', 'body' => '
<p>In the <b>Duration</b> card you can set minutes and seconds for this division only. It applies to its pending matches (already played ones do not change). To change the duration of a whole group of categories, use the tournament Settings.</p>'],
            ['id' => 'proyector-llave', 'title' => 'Bracket projector view', 'body' => '
<p><b class="hb">🖵 Projector view</b> opens the bracket full screen for the mat: it refreshes itself every 15 seconds, fits itself to the screen, shows the configured ads and needs no interaction. When the division ends it shows the podium.</p>'],
        ]],

        ['id' => 'luchas', 'icon' => 'swords', 'title' => 'Matches tab', 'topics' => [
            ['id' => 'luchas-lista', 'title' => 'The full match list', 'body' => '
<p>Every real match in the tournament (byes are not listed) in a single list: <b>live</b> ones first, then <b>pending</b> ones in the configured running order, and finished ones at the bottom.</p>
<ul>
<li><b class="hb">⏱ Operator</b>: opens that match\'s control table.</li>
<li><b class="hb">🖵</b>: opens its projectable scoreboard.</li>
<li>Finished matches show the result and the method; the 🥉 icon marks third-place matches.</li>
</ul>'],
        ]],

        ['id' => 'operador', 'icon' => 'timer', 'title' => 'Table operator (scoreboard)', 'topics' => [
            ['id' => 'operador-flujo', 'title' => 'Full flow of a match', 'body' => '
<ol>
<li>Open the match with <b class="hb">⏱ Operator</b> (from Operation, Matches or the bracket).</li>
<li>Click <b class="hb">🖵 Open display</b> and move that window to the mat\'s projector/TV.</li>
<li>Click <b class="hb">▶ Start</b> to start the timer. <b>Until then the scoring buttons are greyed out</b> on purpose, to avoid accidental taps.</li>
<li>Enter points, advantages and penalties with each side\'s buttons.</li>
<li>Click <b class="hb">End match</b>, pick the winner and the method. The winner advances automatically to the next round (and the semifinal loser, to the bronze match).</li>
</ol>'],
            ['id' => 'operador-botones', 'title' => 'What each button does', 'body' => '
<ul>
<li><b class="hb">▶ Start</b> / <b class="hb">⏸ Pause</b>: starts or pauses the timer (it lives on the server: reloading the page does not lose it).</li>
<li><b class="hb">↺ Reset</b>: returns the timer to the full duration.</li>
<li><b>Points</b>: Takedown, Sweep, Knee on belly, Guard pass, Mount and Back control add the configured points (2/2/2/3/4/4 by default, editable by the admin).</li>
<li><b class="hb">Advantage</b> and <b class="hb">Penalty</b>: add 1 to the corresponding counter.</li>
<li><b class="hb">↩ Undo</b>: reverts the last entered action (you can press it repeatedly).</li>
<li><b class="hb">End match</b>: opens the winner and method selection (By points, Submission, Decision, Disqualification, W.O.).</li>
</ul>
<p>The scoreboard sides are <b>white</b> and <b>yellow/green</b> to tell the competitors apart.</p>'],
            ['id' => 'editar-resultado', 'title' => 'Fixing an already finished match', 'body' => '
<p>If you closed a match with the wrong winner or method, open its operator again and click <b class="hb">✎ Edit result</b>: the match reopens, you pick winner and method again, and the bracket advancement fixes itself.</p>
<p><b>Restriction</b>: only possible if the next match (and the bronze match, if any) has not started yet. If it has, fix that one first.</p>'],
            ['id' => 'fin-torneo', 'title' => 'When the last match ends', 'body' => '
<p>When you close the tournament\'s last match, a "Tournament finished!" notice appears. Automatically: the tournament switches to <b>Finished</b>, the ranking is recalculated and the certificates are generated and sent. Nothing else to do.</p>'],
        ]],

        ['id' => 'marcador', 'icon' => 'screen', 'title' => 'Projectable scoreboard', 'topics' => [
            ['id' => 'marcador-pantalla', 'title' => 'The mat screen', 'body' => '
<p>The scoreboard shows the giant timer and, for each competitor: their photo (if uploaded), name, academy, points, advantages and penalties. One side is white and the other half yellow / half green.</p>
<p>It updates itself with whatever the operator enters — no interaction needed. When the match ends it shows the winner band with the method. If ads are configured, they rotate in ribbons at the top and bottom.</p>'],
        ]],

        ['id' => 'dashboard-torneo', 'icon' => 'chart', 'title' => 'Dashboard tab (statistics)', 'topics' => [
            ['id' => 'dashboard-stats', 'title' => 'Statistics and medal table', 'body' => '
<p>A live summary of the tournament: winning academy, who fought the most, most mat minutes, most submissions, fastest submission, most points scored, most advantages and most losses; plus match and mat-time totals, and the breakdown of wins by method.</p>
<p>The <b>medal table by academy</b> adds up each academy\'s golds, silvers and bronzes as divisions close.</p>'],
        ]],

        ['id' => 'certificados', 'icon' => 'award', 'title' => 'Certificates tab', 'topics' => [
            ['id' => 'certificados-auto', 'title' => 'How and when they are generated', 'body' => '
<p>Certificates are automatic: every time a division finishes, its podium certificates (gold, silver, bronze) plus participation certificates for anyone who did not have one yet are generated and emailed. No need to wait for the tournament to end.</p>
<p>Each PDF carries the tournament name, the competitor\'s name, their category, academy, logos, a seal and a unique <b>verification code</b>. Gi tournaments include the belt drawing; NoGi shows the category (Amateur / Semi Pro / Pro or Kids and juveniles) without a belt.</p>'],
            ['id' => 'certificados-manual', 'title' => 'The Send certificates button and downloads', 'body' => '
<p>The <b class="hb">Send certificates</b> button triggers a manual batch: useful if an email failed or you want to force sending early. You can choose whether to include podium and/or participation. Safe to repeat: it never re-sends what was already sent.</p>
<p>In the list of generated certificates you can <b class="hb">Download</b> each PDF directly.</p>'],
        ]],

        ['id' => 'config-torneo', 'icon' => 'settings', 'title' => 'Tournament Settings tab', 'topics' => [
            ['id' => 'link-inscripcion', 'title' => 'Registration link', 'body' => '
<p>At the very top is the public registration link. Click <b class="hb">Copy link</b> and share it on WhatsApp, social media or email: anyone with the link can register while the tournament is in "Registration open" and there is room left.</p>'],
            ['id' => 'config-datos', 'title' => 'General details and status', 'body' => '
<p>You can change name, date, limit, logo and discipline. The <b>Status</b> field lets you force the tournament status manually (Draft / Registration open / Running / Finished), though it normally changes on its own: to "Running" when the date arrives and to "Finished" when the last match closes.</p>'],
            ['id' => 'config-staff', 'title' => 'Tournament staff (referees and table)', 'body' => '
<p>Add referees and control-table people by email (they need an existing account). They will see the tournament in their panel with access to operate brackets, timers and results — but they cannot clone or delete the tournament. Remove them with <b class="hb">✕</b>.</p>'],
            ['id' => 'config-ordenes', 'title' => 'Running, age and weight orders (mid-tournament)', 'body' => '
<p>The same three draggable lists from creation. Changing them instantly reorders "Next fights", "Matches" and "Divisions" — without touching brackets or results. Each has a <b class="hb">Use the general order</b> button to fall back to the site-wide value.</p>'],
            ['id' => 'config-duracion', 'title' => 'Match duration (re-applies to existing)', 'body' => '
<p>When you save a new duration (per belt in Gi, per category in NoGi), it re-applies automatically to existing divisions and their <b>pending</b> matches — matches already played or live do not change.</p>'],
            ['id' => 'config-niveles', 'title' => 'NoGi tiers (belt → tier mapping)', 'body' => '
<p>Only visible in NoGi tournaments. It defines which tier (Amateur / Semi Pro / Pro) each real belt maps to. If you change it with divisions already generated, the system sorts itself out: it creates any missing divisions and deletes the ones left empty <b>with no matches</b>; if an affected division already has matches, it is kept for you to resolve manually.</p>'],
            ['id' => 'config-clonar-eliminar', 'title' => 'Cloning and deleting the tournament', 'body' => '
<p><b class="hb">⤨ Clone tournament</b> creates a new Draft tournament with the same academies, professors and full configuration — no registrants or brackets. Ideal for recurring events. An admin can also assign the clone to another organizer.</p>
<p>The <b>Danger zone</b> holds <b class="hb">Delete tournament</b>: it erases everything forever (registrants, brackets, results, certificates). It shows what you are about to lose and asks you to type the exact tournament name to confirm.</p>'],
        ]],

        ['id' => 'inscribirse', 'icon' => 'user', 'title' => 'Registering for a tournament (competitor)', 'topics' => [
            ['id' => 'form-inscripcion', 'title' => 'Filling in the form', 'body' => '
<ol>
<li>Open the registration link the organizer shared with you.</li>
<li>Fill in name, email, gender, birthdate, weight and belt. Your age and weight category are computed automatically from your data.</li>
<li>Photo (optional): shown on your matches\' scoreboard and in the ranking.</li>
<li>Pick your academy and professor from the tournament\'s lists.</li>
<li>If you do not have an account, set a password so you can follow the tournament online.</li>
<li>Submit and <b>confirm the email you receive</b> — without confirming, your registration does not enter the brackets.</li>
</ol>'],
            ['id' => 'categoria-o-absoluto', 'title' => 'Category, Absolute or both', 'body' => '
<p>The form has two checkboxes:</p>
<ul>
<li><b>Category</b>: you compete in your regular age + weight + belt (or tier in NoGi) bracket.</li>
<li><b>Absolute</b>: a bracket with no weight or age limit, everyone from the same belt/tier together.</li>
<li>You can tick <b>both</b> and compete in both brackets on the same day.</li>
</ul>
<p>The Absolute is not available for kids, juveniles, white belts (Gi) or Amateur tier (NoGi) — the checkbox disables itself in those cases.</p>'],
            ['id' => 'seguir-torneo', 'title' => 'Following your matches during the tournament', 'body' => '
<p>Log in with your email and password: in <b>My panel</b> you see your next opponent, your match results and the button to watch your position in the bracket in real time. When your division ends, your certificate arrives by email.</p>'],
        ]],

        ['id' => 'rankings', 'icon' => 'chart', 'title' => 'Rankings', 'topics' => [
            ['id' => 'rankings-uso', 'title' => 'Tabs, filters and how points are computed', 'body' => '
<p>There are two separate rankings: <b>Gi</b> and <b>NoGi</b> (tabs at the top) — each tournament adds only to its own discipline.</p>
<ul>
<li>Filters: gender, age and weight. In Gi also by belt; in NoGi by category (Kids/Juveniles, Amateur, Semi Pro, Pro).</li>
<li>Points (admin-configurable): gold 9, silver 3, bronze 1, win 2 and +1 per submission. If someone competes in Category and Absolute, they earn the podium of both brackets.</li>
<li>A competitor\'s identity is their email: points accumulate across tournaments.</li>
</ul>'],
        ]],

        ['id' => 'administracion', 'icon' => 'sliders', 'title' => 'Administration (admin only)', 'topics' => [
            ['id' => 'admin-config', 'title' => 'Site-wide settings', 'body' => '
<p>Under <b>Administration → Settings</b> you define the defaults for the whole site (each tournament can later override them):</p>
<ul>
<li><b>Site name</b>, <b>tournaments per week</b> per organizer, and the <b>retention in months</b> to auto-delete old tournaments (0 = never).</li>
<li><b>Running orders</b> for Gi and NoGi, age order and weight order (draggable lists).</li>
<li><b>Match durations</b> for Gi (per belt) and NoGi (per category), and the kids/juvenile <b>age cutoffs</b>.</li>
<li><b>NoGi tiers</b>: the general belt → Amateur/Semi Pro/Pro mapping.</li>
<li><b>SMTP</b>: the outgoing mail server, with a <b class="hb">Send test</b> button to verify it works.</li>
<li><b>Scoreboard action points</b> (takedown, sweep, etc.) and <b>ranking points</b> (gold/silver/bronze/win/submission).</li>
</ul>'],
            ['id' => 'admin-usuarios', 'title' => 'Users', 'body' => '
<p>Create, edit and delete users, with role <b>user</b> (organizer) or <b>admin</b>. From here you can also verify a user\'s email manually. An admin cannot delete themselves.</p>'],
            ['id' => 'admin-publicidad', 'title' => 'Ads', 'body' => '
<p>Ads rotate on the projected screens (brackets and scoreboards). Each ad can be <b>text</b> or <b>image/banner</b>, with a duration in seconds and an animation (carousel, fade, zoom or continuous ticker). Scope can be <b>general</b> (all tournaments) or <b>a specific tournament</b>; and each tournament chooses in "Ads per tournament" which mix it uses (general + own, own only, general only or none).</p>'],
            ['id' => 'admin-schedulers', 'title' => 'Schedulers / Cron', 'body' => '
<p>Lists the scheduled tasks with their last run and a <b class="hb">▶ Run now</b> button to trigger them manually: <b>emails</b> (mail queue), <b>certificates</b> (pending certificate batches), <b>rankings</b> (recalculation), <b>tournament_status</b> (switches tournaments to "Running"/"Finished"), <b>cleanup</b> (removes unverified registrations and old mails) and <b>delete_old_tournaments</b> (deletes tournaments older than the configured retention). Below are the lines ready to paste into the server crontab.</p>'],
        ]],
    ],
];
