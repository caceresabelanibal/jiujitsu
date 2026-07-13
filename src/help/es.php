<?php
// Centro de ayuda — español. Cada seccion = una pantalla/area de la app;
// cada tema = un boton, menu o flujo explicado paso a paso. El "body" es HTML
// de autoria propia (no lleva e()). <b class="hb">...</b> = un boton de la app.
return [
    'title' => 'Centro de ayuda',
    'intro' => 'Acá está explicada cada pantalla, botón y configuración de Taninzu, paso a paso. Usá el buscador o el índice de la izquierda.',
    'sections' => [

        ['id' => 'primeros-pasos', 'icon' => 'play', 'title' => 'Primeros pasos', 'topics' => [
            ['id' => 'que-es', 'title' => '¿Qué es Taninzu?', 'body' => '
<p>Taninzu es una plataforma para organizar torneos de Jiu-Jitsu de punta a punta: inscripciones online, armado de llaves, marcador con cronómetro para la mesa de control, pantallas para proyectar en el tatami, certificados en PDF enviados por mail y rankings de competidores.</p>
<p>Hay tres tipos de usuario: el <b>organizador</b> (crea y administra torneos), el <b>personal del torneo</b> (árbitros y mesa de control, invitados por el organizador) y el <b>competidor</b> (se inscribe con un link y sigue sus luchas desde su panel).</p>'],
            ['id' => 'crear-cuenta', 'title' => 'Crear una cuenta', 'body' => '
<ol>
<li>Tocá <b class="hb">Crear cuenta</b> arriba a la derecha.</li>
<li>Completá nombre, email y una contraseña de al menos 6 caracteres.</li>
<li>Te llega un mail con un botón para verificar tu dirección — hasta no verificarla no podés ingresar.</li>
</ol>
<p>Si te inscribís a un torneo con el link público y todavía no tenés cuenta, podés crearla en el mismo formulario de inscripción definiendo una contraseña: al confirmar la inscripción por mail también se verifica la cuenta.</p>'],
            ['id' => 'idioma-tema', 'title' => 'Cambiar idioma y tema (claro/oscuro)', 'body' => '
<p>Arriba a la derecha hay dos controles:</p>
<ul>
<li>El <b>desplegable de idioma</b> (Español / English / Português). La primera vez se detecta el idioma de tu navegador; si lo cambiás a mano, tu elección queda guardada.</li>
<li>El botón <b class="hb">◐</b> alterna entre tema claro y oscuro. Las pantallas de proyección (marcador y llaves) siempre se ven oscuras, pensadas para el proyector.</li>
</ul>'],
        ]],

        ['id' => 'mi-panel', 'icon' => 'home', 'title' => 'Mi panel', 'topics' => [
            ['id' => 'mis-torneos', 'title' => 'Mis torneos: qué hace cada botón', 'body' => '
<p>La tabla muestra los torneos que creaste y aquellos donde sos personal (árbitro/mesa). Cada fila tiene:</p>
<ul>
<li><b class="hb">▶ Ir al torneo</b>: abre la pantalla de operación (pestaña Desarrollo) — es tu centro de mando el día del evento.</li>
<li><b class="hb">⚙</b>: abre la Configuración del torneo (datos, personal, órdenes de lucha, duraciones, etc.).</li>
<li><b class="hb">⤨</b> (clonar): crea un torneo nuevo copiando estructura y configuración, sin inscriptos ni llaves. Solo lo ve el dueño del torneo o un admin.</li>
<li><b class="hb">🗑</b> (eliminar): borra el torneo para siempre — pide escribir el nombre exacto para confirmar. Solo dueño o admin.</li>
</ul>
<p>El estado de cada torneo se muestra con una etiqueta: <b>Borrador</b>, <b>Inscripción abierta</b>, <b>En curso</b> o <b>Finalizado</b>.</p>'],
            ['id' => 'mis-inscripciones', 'title' => 'Mis inscripciones (competidor)', 'body' => '
<p>Debajo de tus torneos aparecen los torneos donde estás inscripto como competidor. Para cada uno ves:</p>
<ul>
<li>Tu categoría (edad, cinturón y peso) y si tu inscripción está <b>Verificada</b> o <b>Pendiente</b> (revisá tu mail si sigue pendiente).</li>
<li><b>Próximo rival</b>: contra quién y en qué ronda te toca luchar.</li>
<li>La lista de tus luchas con el resultado (Ganaste/Perdiste) y el marcador.</li>
<li><b class="hb">Tu posición en la llave</b>: abre la llave completa de tu división. Si competís también en Absoluto, aparece un segundo botón para esa llave.</li>
</ul>'],
        ]],

        ['id' => 'crear-torneo', 'icon' => 'trophy', 'title' => 'Crear un torneo', 'topics' => [
            ['id' => 'datos-basicos', 'title' => 'Datos básicos del torneo', 'body' => '
<ol>
<li><b>Nombre del torneo</b>: como va a aparecer en todas las pantallas y certificados.</li>
<li><b>Tipo</b>: <b>Interno</b> (una sola academia — se crea automáticamente con el nombre y logo que cargues) u <b>Open</b> (varias academias, que cargás después en la pestaña Academias).</li>
<li><b>Logo</b>: se muestra en el encabezado del torneo, las pantallas proyectadas y los certificados.</li>
<li><b>Fecha del evento</b>: cuando llega esa fecha el torneo pasa solo de "Inscripción abierta" a "En curso".</li>
<li><b>Cupo de participantes</b>: al llegar al máximo, la inscripción se cierra automáticamente.</li>
<li><b>Duración de lucha por defecto</b>: solo se usa para las categorías especiales; las divisiones normales usan la duración por cinturón/categoría (ver más abajo).</li>
</ol>'],
            ['id' => 'disciplina', 'title' => 'Disciplina: Gi o NoGi', 'body' => '
<p><b>Gi (con kimono)</b>: las divisiones se arman por cinturón exacto (Blanco, Azul, Violeta, Marrón, Negro), como siempre.</p>
<p><b>NoGi (sin kimono)</b>: infantiles y juveniles se agrupan solo por edad y peso (el cinturón no importa); adultos y masters se agrupan por <b>nivel</b>: Amateur, Semi Pro o Pro. Qué cinturón cae en cada nivel es configurable — por defecto Blanco y Azul = Amateur, Violeta = Semi Pro, Marrón y Negro = Pro.</p>
<p>Al elegir NoGi, el formulario muestra los selectores del mapeo cinturón→nivel y el orden de corrida cambia a los 4 grupos de NoGi.</p>'],
            ['id' => 'ordenes-crear', 'title' => 'Orden de luchas, de edades y de pesos', 'body' => '
<p>Estas tres listas se ordenan <b>arrastrando</b> los elementos (el número de la izquierda indica la posición):</p>
<ul>
<li><b>Orden de luchas</b>: en qué orden corren los grupos durante el evento. En Gi: infantiles/juveniles primero y después los cinturones (default negro → blanco). En NoGi: infantiles/juveniles, Amateur, Semi Pro y Pro al final.</li>
<li><b>Orden por edad</b>: dentro de cada grupo, en qué orden van Adulto, Master 1, Master 2, etc.</li>
<li><b>Orden por peso</b>: dentro de cada grupo y edad, el orden de los pesos (Gallo, Pluma... Absoluto).</li>
</ul>
<p>Los valores iniciales salen de la configuración general del sitio; si los cambiás acá quedan como orden propio de este torneo. Todo se puede modificar después desde la Configuración del torneo, incluso con el torneo andando — las listas se reordenan al instante.</p>'],
            ['id' => 'duraciones-edades', 'title' => 'Duración de lucha y cortes de edad', 'body' => '
<p><b>Duración de lucha</b>: minutos de cada lucha según el grupo. En Gi es por cinturón (con un valor único para infantiles/juveniles); en NoGi es por categoría (Infantiles/Juveniles, Amateur, Semi Pro, Pro).</p>
<p><b>Edades</b>: hasta qué edad (al 31/12) alguien es Infantil y hasta cuál Juvenil; Adulto empieza después. Solo afecta a inscripciones nuevas.</p>'],
        ]],

        ['id' => 'desarrollo', 'icon' => 'timer', 'title' => 'Pestaña Desarrollo (operación del torneo)', 'topics' => [
            ['id' => 'desarrollo-resumen', 'title' => 'Qué muestra la pantalla', 'body' => '
<p>Es el centro de mando del día del torneo. Arriba: 4 tarjetas con participantes verificados, luchas (jugadas / totales y pendientes), divisiones completadas y la fecha.</p>
<ul>
<li><b>En vivo ahora</b>: las luchas que están corriendo en este momento, con acceso directo al operador y al marcador.</li>
<li><b>Próximas luchas</b>: las siguientes 8 luchas listas para correr, en el orden de corrida configurado. De cada una: la categoría, los dos competidores, el botón <b class="hb">⏱ Operador</b> (abre la mesa de control) y <b class="hb">🖵</b> (abre el marcador para proyectar).</li>
<li><b>Divisiones</b>: todas las divisiones en tarjetas apiladas siguiendo el mismo orden de corrida, con cantidad de inscriptos, estado (Pendiente / luchas restantes / Terminada) y accesos a la llave y a la vista proyector. Las divisiones terminadas se van al final.</li>
</ul>'],
        ]],

        ['id' => 'academias', 'icon' => 'flag', 'title' => 'Pestaña Academias', 'topics' => [
            ['id' => 'academias-uso', 'title' => 'Cargar academias y profesores', 'body' => '
<ol>
<li>Escribí el nombre de la academia y tocá <b class="hb">Agregar academia</b>. Podés subirle un logo.</li>
<li>Para cada academia podés cargar <b>profesores / sedes</b> con <b class="hb">Agregar profesor</b>.</li>
</ol>
<p>Cuando un competidor se inscribe, elige su academia y profesor de estas listas. En un torneo <b>Interno</b> la academia organizadora ya viene creada. El medallero del Dashboard se arma por academia.</p>'],
        ]],

        ['id' => 'inscriptos', 'icon' => 'clipboard', 'title' => 'Pestaña Inscriptos', 'topics' => [
            ['id' => 'inscriptos-tabla', 'title' => 'Leer la tabla de inscriptos', 'body' => '
<p>Cada fila muestra foto (si subió), nombre, email, género, categoría, edad, peso, academia y estado. La lista sigue el orden de corrida del torneo.</p>
<ul>
<li>En torneos <b>Gi</b> la columna de categoría muestra el cinturón real con su chip de color.</li>
<li>En torneos <b>NoGi</b> muestra la categoría con su óvalo de color: <b>Infantiles y juveniles</b> (amarillo), <b>Amateur</b> (blanco), <b>Semi Pro</b> (violeta) o <b>Pro</b> (negro).</li>
<li>Si el peso dice <b>Absoluto</b> (etiqueta dorada), el competidor se anotó solo al absoluto. Si aparece el peso <b>y además</b> la etiqueta, compite en ambas llaves.</li>
<li>Estado <b>Verificado</b> = confirmó su mail. <b>Pendiente</b> = todavía no; los pendientes no entran en las divisiones.</li>
</ul>'],
            ['id' => 'inscriptos-acciones', 'title' => 'Verificar, editar y eliminar inscriptos', 'body' => '
<ul>
<li><b class="hb">✓</b> (solo en pendientes): verifica la inscripción a mano, sin esperar el mail — útil si el mail no llega.</li>
<li><b class="hb">✎</b>: abre la edición del inscripto (ver el tema siguiente).</li>
<li><b class="hb">✕</b>: elimina la inscripción (pide confirmación).</li>
</ul>'],
            ['id' => 'editar-inscripto', 'title' => 'Editar un inscripto / cambiarlo de categoría', 'body' => '
<p>Desde <b class="hb">✎</b> podés corregir cualquier dato: nombre, nacimiento, peso, foto, academia... y también <b>moverlo a otra categoría</b> (cinturón, edad o peso, sin restricciones) — es la forma de unificar a un competidor que quedó sin rivales en su categoría.</p>
<p>También podés cambiar en qué compite: <b>Categoría</b>, <b>Absoluto</b> o <b>Categoría y Absoluto</b>. Ojo: infantiles, juveniles, cinturón blanco (Gi) y nivel Amateur (NoGi) no pueden ir al Absoluto — si el cambio lo dejara inelegible, el sistema lo baja solo a "Categoría" y te avisa.</p>
<p><b>Importante</b>: el cambio no reacomoda las divisiones ya generadas — después de editar, volvé a <b class="hb">Generar divisiones</b> en la pestaña Divisiones y llaves.</p>'],
        ]],

        ['id' => 'divisiones', 'icon' => 'bracket', 'title' => 'Pestaña Divisiones y llaves', 'topics' => [
            ['id' => 'generar-divisiones', 'title' => 'Generar divisiones', 'body' => '
<p>El botón <b class="hb">Generar divisiones</b> crea automáticamente una división por cada combinación de género + categoría + edad + peso que tenga inscriptos <b>verificados</b>. Es seguro tocarlo varias veces: solo agrega las que falten, nunca borra ni duplica.</p>
<p>Cuándo volver a tocarlo: después de verificar inscriptos nuevos, de editar la categoría de alguien, o de cambiar el mapeo de niveles NoGi.</p>'],
            ['id' => 'categoria-especial', 'title' => 'Categorías especiales', 'body' => '
<p>Una categoría especial es una llave armada 100% a tu criterio, sin restricción de cinturón, peso ni edad (por ejemplo "Exhibición" o "Absoluto invitados").</p>
<ol>
<li>Escribí el nombre, elegí el género y tocá <b class="hb">+ Crear</b>.</li>
<li>Entrá a su <b class="hb">Llave</b> y agregá los inscriptos uno por uno con el selector <b class="hb">+ Agregar</b> (podés mezclar cinturones y edades libremente).</li>
<li>Generá la llave normalmente (siembra manual o aleatoria).</li>
</ol>'],
            ['id' => 'divisiones-tabla', 'title' => 'La tabla de divisiones y sus botones', 'body' => '
<p>Cada fila muestra género, categoría, cantidad de competidores, duración de lucha y estado (<b>Pendiente</b> sin llave, <b>Llave</b> generada, <b>Terminada</b>). Botones:</p>
<ul>
<li><b class="hb">Llave</b>: abre la gestión de esa división (armar/regenerar la llave, cambiar duración).</li>
<li><b class="hb">🖵</b>: abre la vista proyector de la llave (pantalla pública).</li>
<li><b class="hb">✕</b>: elimina la división junto con su llave y luchas — los inscriptos no se tocan. Si era una división automática y los inscriptos siguen ahí, "Generar divisiones" la vuelve a crear.</li>
</ul>'],
        ]],

        ['id' => 'armar-llave', 'icon' => 'bracket', 'title' => 'Armar y leer una llave', 'topics' => [
            ['id' => 'siembra', 'title' => 'Siembra: manual o aleatoria', 'body' => '
<ol>
<li>En <b>Competidores</b> ordená a los participantes con los desplegables: la posición define contra quién cruza cada uno (1 vs 2, 3 vs 4...).</li>
<li>Tocá <b class="hb">Guardar llave</b> para generar con ese orden, o <b class="hb">⤨ Aleatorio</b> para sortearlo.</li>
<li>Si la llave ya existía, el botón dice <b class="hb">Regenerar llave</b>: la borra y la arma de nuevo (se pierden los resultados cargados de esa división).</li>
</ol>
<p>Si la cantidad no es potencia de 2, el sistema reparte <b>byes</b> (pases automáticos de ronda) según la siembra estándar. Con 4 o más competidores se crea también la lucha por el <b>tercer puesto</b> entre los perdedores de las semifinales.</p>'],
            ['id' => 'leer-llave', 'title' => 'Cómo leer la llave', 'body' => '
<ul>
<li>Cada columna es una ronda (Ronda 1, Semifinal, Final); las líneas conectan cada lucha con la siguiente.</li>
<li>El ganador de cada lucha queda resaltado y lleva la copita dorada 🏆; en la Final, el perdedor lleva la copita gris (2° puesto).</li>
<li>"A definir" = ese lugar se completa cuando termine la lucha anterior.</li>
<li>Debajo de cada lucha aparece el método (Por puntos, Finalización...) o el enlace <b class="hb">⏱ Operador</b> si está pendiente.</li>
<li>Cuando la división termina, el podio (oro, plata, bronce) aparece a la derecha de la llave.</li>
</ul>'],
            ['id' => 'duracion-division', 'title' => 'Cambiar la duración de una división puntual', 'body' => '
<p>En la tarjeta <b>Duración</b> podés fijar minutos y segundos solo para esta división. Se aplica a sus luchas pendientes (las ya jugadas no cambian). Para cambiar la duración de todo un grupo de categorías usá la Configuración del torneo.</p>'],
            ['id' => 'proyector-llave', 'title' => 'Vista proyector de la llave', 'body' => '
<p><b class="hb">🖵 Vista proyector</b> abre la llave en pantalla completa para el tatami: se actualiza sola cada 15 segundos, se ajusta al tamaño de la pantalla, muestra la publicidad configurada y no necesita que nadie la toque. Al terminar la división muestra el podio.</p>'],
        ]],

        ['id' => 'luchas', 'icon' => 'swords', 'title' => 'Pestaña Luchas', 'topics' => [
            ['id' => 'luchas-lista', 'title' => 'La lista completa de luchas', 'body' => '
<p>Todas las luchas reales del torneo (los byes no aparecen) en una sola lista: primero las <b>en vivo</b>, después las <b>pendientes</b> en el orden de corrida configurado, y al final las terminadas.</p>
<ul>
<li><b class="hb">⏱ Operador</b>: abre la mesa de control de esa lucha.</li>
<li><b class="hb">🖵</b>: abre su marcador para proyectar.</li>
<li>En las terminadas se ve el resultado y el método; el ícono 🥉 marca las luchas por el tercer puesto.</li>
</ul>'],
        ]],

        ['id' => 'operador', 'icon' => 'timer', 'title' => 'Operador de mesa (marcador)', 'topics' => [
            ['id' => 'operador-flujo', 'title' => 'Flujo completo de una lucha', 'body' => '
<ol>
<li>Abrí la lucha con <b class="hb">⏱ Operador</b> (desde Desarrollo, Luchas o la llave).</li>
<li>Tocá <b class="hb">🖵 Abrir marcador</b> y llevá esa ventana al proyector/TV del tatami.</li>
<li>Tocá <b class="hb">▶ Iniciar</b> para arrancar el cronómetro. <b>Hasta ese momento los botones de puntaje están grisados</b> a propósito, para evitar toques accidentales.</li>
<li>Cargá puntos, ventajas y penalizaciones con los botones de cada lado.</li>
<li>Tocá <b class="hb">Finalizar lucha</b>, elegí el ganador y el método. El ganador avanza solo a la siguiente ronda (y el perdedor de semifinal, al bronce).</li>
</ol>'],
            ['id' => 'operador-botones', 'title' => 'Qué hace cada botón', 'body' => '
<ul>
<li><b class="hb">▶ Iniciar</b> / <b class="hb">⏸ Pausa</b>: arranca o pausa el cronómetro (vive en el servidor: si se recarga la página no se pierde).</li>
<li><b class="hb">↺ Reiniciar</b>: vuelve el cronómetro a la duración completa.</li>
<li><b>Puntos</b>: Derribo, Raspaje, Rodilla en panza, Pasaje de guardia, Montada y Control de espalda suman los puntos configurados (2/2/2/3/4/4 por defecto, editables por el admin).</li>
<li><b class="hb">Ventaja</b> y <b class="hb">Penalización</b>: suman 1 al contador correspondiente.</li>
<li><b class="hb">↩ Deshacer</b>: revierte la última acción cargada (podés tocarlo varias veces).</li>
<li><b class="hb">Finalizar lucha</b>: abre la selección de ganador y método (Por puntos, Finalización, Decisión, Descalificación, W.O.).</li>
</ul>
<p>Los lados del marcador son <b>blanco</b> y <b>amarillo/verde</b> para distinguir a los competidores.</p>'],
            ['id' => 'editar-resultado', 'title' => 'Corregir una lucha ya terminada', 'body' => '
<p>Si cerraste una lucha con el ganador o método equivocado, entrá de nuevo a su operador y tocá <b class="hb">✎ Editar resultado</b>: la lucha se reabre, elegís ganador y método de nuevo, y el avance en la llave se corrige solo.</p>
<p><b>Restricción</b>: solo se puede si la lucha siguiente (y la de bronce, si aplica) todavía no empezó. Si ya empezó, corregí primero aquella.</p>'],
            ['id' => 'fin-torneo', 'title' => 'Cuando termina la última lucha', 'body' => '
<p>Al cerrar la última lucha del torneo aparece un aviso de "¡Torneo finalizado!". Automáticamente: el torneo pasa a <b>Finalizado</b>, se recalcula el ranking y se generan y envían los certificados. No hay que hacer nada más.</p>'],
        ]],

        ['id' => 'marcador', 'icon' => 'screen', 'title' => 'Marcador para proyectar', 'topics' => [
            ['id' => 'marcador-pantalla', 'title' => 'La pantalla del tatami', 'body' => '
<p>El marcador muestra el cronómetro gigante, y para cada competidor: su foto (si subió), nombre, academia, puntos, ventajas y penalizaciones. Un lado es blanco y el otro mitad amarillo/mitad verde.</p>
<p>Se actualiza solo con lo que carga el operador — no hay que tocarla. Al finalizar muestra la banda con el ganador y el método. Si hay publicidad configurada, rota en cintas arriba y abajo.</p>'],
        ]],

        ['id' => 'dashboard-torneo', 'icon' => 'chart', 'title' => 'Pestaña Dashboard (estadísticas)', 'topics' => [
            ['id' => 'dashboard-stats', 'title' => 'Estadísticas y medallero', 'body' => '
<p>Resumen en vivo del torneo: academia ganadora, quién luchó más, más minutos en tatami, más finalizaciones, la finalización más rápida, más puntos anotados, más ventajas y más derrotas; además de totales de luchas y tiempo de tatami, y el desglose de victorias por método.</p>
<p>El <b>medallero por academia</b> suma los oros, platas y bronces de cada academia a medida que se cierran las divisiones.</p>'],
        ]],

        ['id' => 'certificados', 'icon' => 'award', 'title' => 'Pestaña Certificados', 'topics' => [
            ['id' => 'certificados-auto', 'title' => 'Cómo y cuándo se generan', 'body' => '
<p>Los certificados salen solos: cada vez que una división termina, se generan y envían por mail los del podio de esa división (oro, plata, bronce) y los de participación de quienes aún no lo tenían. No hace falta esperar al final del torneo.</p>
<p>Cada PDF lleva el nombre del torneo, el del competidor, su categoría, la academia, los logos, un sello y un <b>código de verificación</b> único. En torneos Gi incluye el dibujo del cinturón; en NoGi muestra la categoría (Amateur / Semi Pro / Pro o Infantiles y juveniles) sin cinturón.</p>'],
            ['id' => 'certificados-manual', 'title' => 'El botón Enviar certificados y la descarga', 'body' => '
<p>El botón <b class="hb">Enviar certificados</b> dispara un lote manual: útil si un mail falló o si querés forzar el envío antes de tiempo. Podés elegir si incluir podio y/o participación. Es seguro repetirlo: nunca reenvía lo que ya se envió.</p>
<p>En la lista de certificados generados podés <b class="hb">Descargar</b> cada PDF directamente.</p>'],
        ]],

        ['id' => 'config-torneo', 'icon' => 'settings', 'title' => 'Pestaña Configuración del torneo', 'topics' => [
            ['id' => 'link-inscripcion', 'title' => 'Link de inscripción', 'body' => '
<p>Arriba de todo está el link público de inscripción. Tocá <b class="hb">Copiar link</b> y compartilo por WhatsApp, redes o mail: cualquiera con el link puede inscribirse mientras el torneo esté en "Inscripción abierta" y quede cupo.</p>'],
            ['id' => 'config-datos', 'title' => 'Datos generales y estado', 'body' => '
<p>Podés cambiar nombre, fecha, cupo, logo y disciplina. El campo <b>Estado</b> permite forzar a mano el estado del torneo (Borrador / Inscripción abierta / En curso / Finalizado), aunque normalmente cambia solo: pasa a "En curso" al llegar la fecha y a "Finalizado" al cerrarse la última lucha.</p>'],
            ['id' => 'config-staff', 'title' => 'Personal del torneo (árbitros y mesa)', 'body' => '
<p>Agregá por email a árbitros y mesa de control (necesitan tener cuenta creada). Van a ver el torneo en su panel con acceso a operar llaves, cronómetros y resultados — pero no pueden clonar ni eliminar el torneo. Con <b class="hb">✕</b> los sacás.</p>'],
            ['id' => 'config-ordenes', 'title' => 'Órdenes de lucha, edad y peso (con el torneo andando)', 'body' => '
<p>Las mismas tres listas arrastrables de la creación. Cambiarlas reordena al instante "Próximas luchas", "Luchas" y "Divisiones" — sin tocar llaves ni resultados. Cada una tiene un botón <b class="hb">Usar el orden general</b> para volver al valor del sitio.</p>'],
            ['id' => 'config-duracion', 'title' => 'Duración de lucha (re-aplica a lo ya creado)', 'body' => '
<p>Al guardar una duración nueva (por cinturón en Gi, por categoría en NoGi), se re-aplica automáticamente a las divisiones existentes y sus luchas <b>pendientes</b> — las que ya se jugaron o están en vivo no cambian.</p>'],
            ['id' => 'config-niveles', 'title' => 'Niveles NoGi (mapeo cinturón → nivel)', 'body' => '
<p>Solo visible en torneos NoGi. Define a qué nivel (Amateur / Semi Pro / Pro) corresponde cada cinturón real. Si lo cambiás con divisiones ya generadas, el sistema acomoda solo: crea las divisiones nuevas que hagan falta y borra las que quedaron vacías <b>sin luchas</b>; si una división afectada ya tiene luchas cargadas, se conserva y la resolvés a mano.</p>'],
            ['id' => 'config-clonar-eliminar', 'title' => 'Clonar y eliminar el torneo', 'body' => '
<p><b class="hb">⤨ Clonar torneo</b> crea uno nuevo en Borrador con las mismas academias, profesores y toda la configuración — sin inscriptos ni llaves. Ideal para eventos que se repiten. Un admin puede además asignarle el clon a otro organizador.</p>
<p>La <b>Zona de riesgo</b> tiene <b class="hb">Eliminar torneo</b>: borra todo para siempre (inscriptos, llaves, resultados, certificados). Te muestra cuánto vas a perder y te pide escribir el nombre exacto del torneo para confirmar.</p>'],
        ]],

        ['id' => 'inscribirse', 'icon' => 'user', 'title' => 'Inscribirse a un torneo (competidor)', 'topics' => [
            ['id' => 'form-inscripcion', 'title' => 'Completar el formulario', 'body' => '
<ol>
<li>Abrí el link de inscripción que te pasó el organizador.</li>
<li>Completá nombre, email, género, fecha de nacimiento, peso y cinturón. La categoría de edad y peso se calcula sola con tus datos.</li>
<li>Foto (opcional): se muestra en el marcador de tus luchas y en el ranking.</li>
<li>Elegí academia y profesor de las listas del torneo.</li>
<li>Si no tenés cuenta, definí una contraseña para poder seguir el torneo online.</li>
<li>Enviá y <b>confirmá el mail que te llega</b> — sin confirmar, tu inscripción no entra en las llaves.</li>
</ol>'],
            ['id' => 'categoria-o-absoluto', 'title' => 'Categoría, Absoluto o ambas', 'body' => '
<p>En el formulario hay dos casillas:</p>
<ul>
<li><b>Categoría</b>: competís en tu llave normal de edad + peso + cinturón (o nivel en NoGi).</li>
<li><b>Absoluto</b>: una llave sin límite de peso ni edad, todos los del mismo cinturón/nivel juntos.</li>
<li>Podés tildar <b>las dos</b> y competir en ambas llaves el mismo día.</li>
</ul>
<p>El Absoluto no está disponible para infantiles, juveniles, cinturón blanco (Gi) ni nivel Amateur (NoGi) — la casilla se deshabilita sola en esos casos.</p>'],
            ['id' => 'seguir-torneo', 'title' => 'Seguir tus luchas durante el torneo', 'body' => '
<p>Ingresá con tu email y contraseña: en <b>Mi panel</b> ves tu próximo rival, los resultados de tus luchas y el botón para ver tu posición en la llave en tiempo real. Al terminar tu división te llega el certificado por mail.</p>'],
        ]],

        ['id' => 'rankings', 'icon' => 'chart', 'title' => 'Rankings', 'topics' => [
            ['id' => 'rankings-uso', 'title' => 'Pestañas, filtros y cómo se calculan los puntos', 'body' => '
<p>Hay dos rankings separados: <b>Gi</b> y <b>NoGi</b> (pestañas arriba) — cada torneo suma solo al de su disciplina.</p>
<ul>
<li>Filtros: género, edad y peso. En Gi también por cinturón; en NoGi por categoría (Infantiles/Juveniles, Amateur, Semi Pro, Pro).</li>
<li>Puntos (configurables por el admin): oro 9, plata 3, bronce 1, victoria 2 y +1 por finalización. Si alguien compite en Categoría y Absoluto, suma el podio de las dos llaves.</li>
<li>La identidad del competidor es su email: sus puntos se acumulan entre torneos.</li>
</ul>'],
        ]],

        ['id' => 'administracion', 'icon' => 'sliders', 'title' => 'Administración (solo admin)', 'topics' => [
            ['id' => 'admin-config', 'title' => 'Configuración general del sitio', 'body' => '
<p>En <b>Administración → Configuración</b> se definen los valores por defecto de todo el sitio (cada torneo puede después pisarlos con valores propios):</p>
<ul>
<li><b>Nombre del sitio</b>, <b>torneos por semana</b> por organizador, y la <b>retención en meses</b> para borrar torneos viejos automáticamente (0 = nunca).</li>
<li><b>Órdenes de lucha</b> Gi y NoGi, orden por edad y por peso (listas arrastrables).</li>
<li><b>Duraciones de lucha</b> Gi (por cinturón) y NoGi (por categoría), y los <b>cortes de edad</b> infantil/juvenil.</li>
<li><b>Niveles NoGi</b>: el mapeo general cinturón → Amateur/Semi Pro/Pro.</li>
<li><b>SMTP</b>: el servidor de correo saliente, con el botón <b class="hb">Enviar prueba</b> para verificar que funcione.</li>
<li><b>Puntaje de acciones</b> del marcador (derribo, raspaje, etc.) y <b>puntaje del ranking</b> (oro/plata/bronce/victoria/finalización).</li>
</ul>'],
            ['id' => 'admin-usuarios', 'title' => 'Usuarios', 'body' => '
<p>Alta, edición y borrado de usuarios, con rol <b>user</b> (organizador) o <b>admin</b>. Desde acá también se puede verificar el email de un usuario a mano. Un admin no puede eliminarse a sí mismo.</p>'],
            ['id' => 'admin-publicidad', 'title' => 'Publicidad', 'body' => '
<p>Los avisos rotan en las pantallas proyectadas (llaves y marcadores). Cada aviso puede ser <b>texto</b> o <b>imagen/banner</b>, con duración en segundos y animación (carrusel, fundido, zoom o cinta continua). El alcance puede ser <b>general</b> (todos los torneos) o de <b>un torneo puntual</b>; y cada torneo define en "Publicidad por torneo" qué mezcla usa (generales + propias, solo propias, solo generales o ninguna).</p>'],
            ['id' => 'admin-schedulers', 'title' => 'Schedulers / Cron', 'body' => '
<p>Lista las tareas programadas con su última ejecución y un botón <b class="hb">▶ Ejecutar ahora</b> para dispararlas a mano: <b>emails</b> (cola de correo), <b>certificates</b> (lotes de certificados pendientes), <b>rankings</b> (recálculo), <b>tournament_status</b> (pasa torneos a "En curso"/"Finalizado"), <b>cleanup</b> (limpieza de inscripciones no verificadas y mails viejos) y <b>delete_old_tournaments</b> (borra torneos más viejos que la retención configurada). Abajo están las líneas listas para pegar en el crontab del servidor.</p>'],
        ]],
    ],
];
