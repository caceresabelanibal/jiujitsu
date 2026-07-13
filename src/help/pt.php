<?php
// Central de ajuda — português. Mesma estrutura/âncoras que es.php (ids não se traduzem).
return [
    'title' => 'Central de ajuda',
    'intro' => 'Cada tela, botão e configuração do Taninzu explicados passo a passo. Use a busca ou o índice à esquerda.',
    'sections' => [

        ['id' => 'primeros-pasos', 'icon' => 'play', 'title' => 'Primeiros passos', 'topics' => [
            ['id' => 'que-es', 'title' => 'O que é o Taninzu?', 'body' => '
<p>O Taninzu é uma plataforma para organizar torneios de Jiu-Jitsu de ponta a ponta: inscrições online, montagem de chaves, placar com cronômetro para a mesa de controle, telas para projetar no tatame, certificados em PDF enviados por email e rankings de competidores.</p>
<p>Há três tipos de usuário: o <b>organizador</b> (cria e administra torneios), a <b>equipe do torneio</b> (árbitros e mesa de controle, convidados pelo organizador) e o <b>competidor</b> (se inscreve por um link e acompanha suas lutas pelo painel).</p>'],
            ['id' => 'crear-cuenta', 'title' => 'Criar uma conta', 'body' => '
<ol>
<li>Clique em <b class="hb">Criar conta</b> no canto superior direito.</li>
<li>Preencha nome, email e uma senha de pelo menos 6 caracteres.</li>
<li>Você recebe um email com um botão para verificar seu endereço — sem verificar não é possível entrar.</li>
</ol>
<p>Se você se inscrever em um torneio pelo link público e ainda não tiver conta, pode criá-la no próprio formulário de inscrição definindo uma senha: ao confirmar a inscrição por email a conta também é verificada.</p>'],
            ['id' => 'idioma-tema', 'title' => 'Trocar idioma e tema (claro/escuro)', 'body' => '
<p>No canto superior direito há dois controles:</p>
<ul>
<li>O <b>seletor de idioma</b> (Español / English / Português). Na primeira visita o idioma do navegador é detectado; se você trocar manualmente, sua escolha fica salva.</li>
<li>O botão <b class="hb">◐</b> alterna entre tema claro e escuro. As telas de projeção (placar e chaves) sempre aparecem escuras, pensadas para o projetor.</li>
</ul>'],
        ]],

        ['id' => 'mi-panel', 'icon' => 'home', 'title' => 'Meu painel', 'topics' => [
            ['id' => 'mis-torneos', 'title' => 'Meus torneios: o que faz cada botão', 'body' => '
<p>A tabela mostra os torneios que você criou e aqueles onde você é equipe (árbitro/mesa). Cada linha tem:</p>
<ul>
<li><b class="hb">▶ Ir para o torneio</b>: abre a tela de operação (aba Andamento) — seu centro de comando no dia do evento.</li>
<li><b class="hb">⚙</b>: abre as Configurações do torneio (dados, equipe, ordens de luta, durações, etc.).</li>
<li><b class="hb">⤨</b> (clonar): cria um torneio novo copiando estrutura e configuração, sem inscritos nem chaves. Só aparece para o dono ou um admin.</li>
<li><b class="hb">🗑</b> (excluir): apaga o torneio para sempre — pede para digitar o nome exato para confirmar. Somente dono ou admin.</li>
</ul>
<p>Cada torneio mostra uma etiqueta de status: <b>Rascunho</b>, <b>Inscrições abertas</b>, <b>Em andamento</b> ou <b>Finalizado</b>.</p>'],
            ['id' => 'mis-inscripciones', 'title' => 'Minhas inscrições (competidor)', 'body' => '
<p>Abaixo dos seus torneios aparecem os torneios onde você está inscrito como competidor. Para cada um você vê:</p>
<ul>
<li>Sua categoria (idade, faixa e peso) e se a inscrição está <b>Verificada</b> ou <b>Pendente</b> (confira seu email se seguir pendente).</li>
<li><b>Próximo adversário</b>: contra quem e em que rodada você luta.</li>
<li>A lista das suas lutas com o resultado (Você venceu / Você perdeu) e o placar.</li>
<li><b class="hb">Sua posição na chave</b>: abre a chave completa da sua divisão. Se você também compete no Absoluto, aparece um segundo botão para essa chave.</li>
</ul>'],
        ]],

        ['id' => 'crear-torneo', 'icon' => 'trophy', 'title' => 'Criar um torneio', 'topics' => [
            ['id' => 'datos-basicos', 'title' => 'Dados básicos do torneio', 'body' => '
<ol>
<li><b>Nome do torneio</b>: como vai aparecer em todas as telas e certificados.</li>
<li><b>Tipo</b>: <b>Interno</b> (uma única academia — criada automaticamente com o nome e logo que você subir) ou <b>Open</b> (várias academias, cadastradas depois na aba Academias).</li>
<li><b>Logo</b>: aparece no cabeçalho do torneio, nas telas projetadas e nos certificados.</li>
<li><b>Data do evento</b>: quando essa data chega o torneio passa sozinho de "Inscrições abertas" para "Em andamento".</li>
<li><b>Limite de participantes</b>: ao atingir o máximo, as inscrições fecham automaticamente.</li>
<li><b>Duração de luta padrão</b>: usada apenas pelas categorias especiais; as divisões normais usam a duração por faixa/categoria (veja abaixo).</li>
</ol>'],
            ['id' => 'disciplina', 'title' => 'Modalidade: Gi ou NoGi', 'body' => '
<p><b>Gi (com kimono)</b>: as divisões são montadas por faixa exata (Branca, Azul, Roxa, Marrom, Preta), como sempre.</p>
<p><b>NoGi (sem kimono)</b>: infantis e juvenis se agrupam só por idade e peso (a faixa não importa); adultos e masters se agrupam por <b>nível</b>: Amateur, Semi Pro ou Pro. Qual faixa cai em cada nível é configurável — por padrão Branca e Azul = Amateur, Roxa = Semi Pro, Marrom e Preta = Pro.</p>
<p>Ao escolher NoGi, o formulário mostra os seletores do mapeamento faixa→nível e a ordem de corrida muda para os 4 grupos do NoGi.</p>'],
            ['id' => 'ordenes-crear', 'title' => 'Ordem das lutas, das idades e dos pesos', 'body' => '
<p>Estas três listas se ordenam <b>arrastando</b> os itens (o número à esquerda indica a posição):</p>
<ul>
<li><b>Ordem das lutas</b>: em que ordem os grupos rodam durante o evento. No Gi: infantis/juvenis primeiro e depois as faixas (padrão preta → branca). No NoGi: infantis/juvenis, Amateur, Semi Pro e Pro por último.</li>
<li><b>Ordem por idade</b>: dentro de cada grupo, em que ordem vão Adulto, Master 1, Master 2, etc.</li>
<li><b>Ordem por peso</b>: dentro de cada grupo e idade, a ordem dos pesos (Galo, Pluma... Absoluto).</li>
</ul>
<p>Os valores iniciais vêm da configuração geral do site; se você mudar aqui viram a ordem própria deste torneio. Tudo pode ser alterado depois nas Configurações do torneio, mesmo com o torneio rodando — as listas se reordenam na hora.</p>'],
            ['id' => 'duraciones-edades', 'title' => 'Duração de luta e cortes de idade', 'body' => '
<p><b>Duração de luta</b>: minutos de cada luta conforme o grupo. No Gi é por faixa (com um valor único para infantis/juvenis); no NoGi é por categoria (Infantis/Juvenis, Amateur, Semi Pro, Pro).</p>
<p><b>Idades</b>: até que idade (em 31/12) alguém é Infantil e até qual é Juvenil; Adulto começa depois. Só afeta inscrições novas.</p>'],
        ]],

        ['id' => 'desarrollo', 'icon' => 'timer', 'title' => 'Aba Andamento (operação do torneio)', 'topics' => [
            ['id' => 'desarrollo-resumen', 'title' => 'O que a tela mostra', 'body' => '
<p>É o centro de comando do dia do torneio. No topo: 4 cartões com participantes verificados, lutas (disputadas / totais e pendentes), divisões concluídas e a data.</p>
<ul>
<li><b>Ao vivo agora</b>: as lutas rodando neste momento, com acesso direto ao operador e ao placar.</li>
<li><b>Próximas lutas</b>: as próximas 8 lutas prontas para rodar, na ordem de corrida configurada. De cada uma: a categoria, os dois competidores, o botão <b class="hb">⏱ Operador</b> (abre a mesa de controle) e <b class="hb">🖵</b> (abre o placar para projetar).</li>
<li><b>Divisões</b>: todas as divisões em cartões empilhados seguindo a mesma ordem de corrida, com quantidade de inscritos, status (Pendente / lutas restantes / Encerrada) e atalhos para a chave e o modo projetor. As divisões encerradas vão para o final.</li>
</ul>'],
        ]],

        ['id' => 'academias', 'icon' => 'flag', 'title' => 'Aba Academias', 'topics' => [
            ['id' => 'academias-uso', 'title' => 'Cadastrar academias e professores', 'body' => '
<ol>
<li>Digite o nome da academia e clique em <b class="hb">Adicionar academia</b>. Você pode subir um logo.</li>
<li>Para cada academia é possível cadastrar <b>professores / sedes</b> com <b class="hb">Adicionar professor</b>.</li>
</ol>
<p>Quando um competidor se inscreve, escolhe sua academia e professor destas listas. Em um torneio <b>Interno</b> a academia organizadora já vem criada. O quadro de medalhas do Dashboard é montado por academia.</p>'],
        ]],

        ['id' => 'inscriptos', 'icon' => 'clipboard', 'title' => 'Aba Inscritos', 'topics' => [
            ['id' => 'inscriptos-tabla', 'title' => 'Ler a tabela de inscritos', 'body' => '
<p>Cada linha mostra foto (se enviou), nome, email, gênero, categoria, idade, peso, academia e status. A lista segue a ordem de corrida do torneio.</p>
<ul>
<li>Em torneios <b>Gi</b> a coluna de categoria mostra a faixa real com seu chip de cor.</li>
<li>Em torneios <b>NoGi</b> mostra a categoria com seu selo de cor: <b>Infantis e juvenis</b> (amarelo), <b>Amateur</b> (branco), <b>Semi Pro</b> (roxo) ou <b>Pro</b> (preto).</li>
<li>Se o peso diz <b>Absoluto</b> (selo dourado), o competidor se inscreveu só no absoluto. Se aparece o peso <b>e também</b> o selo, compete nas duas chaves.</li>
<li>Status <b>Verificado</b> = confirmou o email. <b>Pendente</b> = ainda não; os pendentes não entram nas divisões.</li>
</ul>'],
            ['id' => 'inscriptos-acciones', 'title' => 'Verificar, editar e excluir inscritos', 'body' => '
<ul>
<li><b class="hb">✓</b> (só nos pendentes): verifica a inscrição manualmente, sem esperar o email — útil se o email não chega.</li>
<li><b class="hb">✎</b>: abre a edição do inscrito (veja o próximo tema).</li>
<li><b class="hb">✕</b>: exclui a inscrição (pede confirmação).</li>
</ul>'],
            ['id' => 'editar-inscripto', 'title' => 'Editar um inscrito / mudá-lo de categoria', 'body' => '
<p>Pelo <b class="hb">✎</b> você pode corrigir qualquer dado: nome, nascimento, peso, foto, academia... e também <b>movê-lo para outra categoria</b> (faixa, idade ou peso, sem restrições) — é a forma de unificar um competidor que ficou sem adversários na categoria dele.</p>
<p>Também dá para mudar em que ele compete: <b>Categoria</b>, <b>Absoluto</b> ou <b>Categoria e Absoluto</b>. Atenção: infantis, juvenis, faixa branca (Gi) e nível Amateur (NoGi) não podem ir ao Absoluto — se a mudança o deixar inelegível, o sistema volta sozinho para "Categoria" e avisa.</p>
<p><b>Importante</b>: a mudança não reacomoda as divisões já geradas — depois de editar, clique de novo em <b class="hb">Gerar divisões</b> na aba Divisões e chaves.</p>'],
        ]],

        ['id' => 'divisiones', 'icon' => 'bracket', 'title' => 'Aba Divisões e chaves', 'topics' => [
            ['id' => 'generar-divisiones', 'title' => 'Gerar divisões', 'body' => '
<p>O botão <b class="hb">Gerar divisões</b> cria automaticamente uma divisão para cada combinação de gênero + categoria + idade + peso que tenha inscritos <b>verificados</b>. É seguro clicar várias vezes: só adiciona o que falta, nunca apaga nem duplica.</p>
<p>Quando clicar de novo: depois de verificar inscritos novos, de editar a categoria de alguém, ou de mudar o mapeamento de níveis NoGi.</p>'],
            ['id' => 'categoria-especial', 'title' => 'Categorias especiais', 'body' => '
<p>Uma categoria especial é uma chave montada 100% a seu critério, sem restrição de faixa, peso nem idade (por exemplo "Exibição" ou "Absoluto convidados").</p>
<ol>
<li>Digite o nome, escolha o gênero e clique em <b class="hb">+ Criar</b>.</li>
<li>Entre na <b class="hb">Chave</b> dela e adicione os inscritos um por um com o seletor <b class="hb">+ Adicionar</b> (pode misturar faixas e idades livremente).</li>
<li>Gere a chave normalmente (sorteio manual ou aleatório).</li>
</ol>'],
            ['id' => 'divisiones-tabla', 'title' => 'A tabela de divisões e seus botões', 'body' => '
<p>Cada linha mostra gênero, categoria, quantidade de competidores, duração de luta e status (<b>Pendente</b> sem chave, <b>Chave</b> gerada, <b>Encerrada</b>). Botões:</p>
<ul>
<li><b class="hb">Chave</b>: abre a gestão dessa divisão (montar/regenerar a chave, mudar a duração).</li>
<li><b class="hb">🖵</b>: abre o modo projetor da chave (tela pública).</li>
<li><b class="hb">✕</b>: exclui a divisão junto com a chave e as lutas — os inscritos não são afetados. Se era uma divisão automática e os inscritos continuam lá, "Gerar divisões" a recria.</li>
</ul>'],
        ]],

        ['id' => 'armar-llave', 'icon' => 'bracket', 'title' => 'Montar e ler uma chave', 'topics' => [
            ['id' => 'siembra', 'title' => 'Sorteio: manual ou aleatório', 'body' => '
<ol>
<li>Em <b>Competidores</b>, ordene os participantes com os seletores: a posição define quem cruza com quem (1 vs 2, 3 vs 4...).</li>
<li>Clique em <b class="hb">Salvar chave</b> para gerar com essa ordem, ou <b class="hb">⤨ Aleatório</b> para sortear.</li>
<li>Se a chave já existia, o botão diz <b class="hb">Regenerar chave</b>: apaga e monta de novo (os resultados já lançados dessa divisão se perdem).</li>
</ol>
<p>Se a quantidade não é potência de 2, o sistema distribui <b>byes</b> (passes automáticos de rodada) conforme o sorteio padrão. Com 4 ou mais competidores também é criada a luta pelo <b>terceiro lugar</b> entre os perdedores das semifinais.</p>'],
            ['id' => 'leer-llave', 'title' => 'Como ler a chave', 'body' => '
<ul>
<li>Cada coluna é uma rodada (Rodada 1, Semifinal, Final); as linhas conectam cada luta com a seguinte.</li>
<li>O vencedor de cada luta fica destacado e leva o troféu dourado 🏆; na Final, o perdedor leva o troféu cinza (2º lugar).</li>
<li>"A definir" = esse lugar se preenche quando a luta anterior terminar.</li>
<li>Abaixo de cada luta aparece o método (Por pontos, Finalização...) ou o link <b class="hb">⏱ Operador</b> se está pendente.</li>
<li>Quando a divisão termina, o pódio (ouro, prata, bronze) aparece à direita da chave.</li>
</ul>'],
            ['id' => 'duracion-division', 'title' => 'Mudar a duração de uma divisão específica', 'body' => '
<p>No cartão <b>Duração</b> você pode definir minutos e segundos só para esta divisão. Aplica-se às lutas pendentes dela (as já disputadas não mudam). Para mudar a duração de um grupo inteiro de categorias use as Configurações do torneio.</p>'],
            ['id' => 'proyector-llave', 'title' => 'Modo projetor da chave', 'body' => '
<p><b class="hb">🖵 Modo projetor</b> abre a chave em tela cheia para o tatame: atualiza sozinha a cada 15 segundos, se ajusta ao tamanho da tela, mostra a publicidade configurada e não precisa de ninguém mexendo. Ao encerrar a divisão mostra o pódio.</p>'],
        ]],

        ['id' => 'luchas', 'icon' => 'swords', 'title' => 'Aba Lutas', 'topics' => [
            ['id' => 'luchas-lista', 'title' => 'A lista completa de lutas', 'body' => '
<p>Todas as lutas reais do torneio (os byes não aparecem) numa única lista: primeiro as <b>ao vivo</b>, depois as <b>pendentes</b> na ordem de corrida configurada, e no final as encerradas.</p>
<ul>
<li><b class="hb">⏱ Operador</b>: abre a mesa de controle dessa luta.</li>
<li><b class="hb">🖵</b>: abre o placar dela para projetar.</li>
<li>Nas encerradas aparece o resultado e o método; o ícone 🥉 marca as lutas pelo terceiro lugar.</li>
</ul>'],
        ]],

        ['id' => 'operador', 'icon' => 'timer', 'title' => 'Operador de mesa (placar)', 'topics' => [
            ['id' => 'operador-flujo', 'title' => 'Fluxo completo de uma luta', 'body' => '
<ol>
<li>Abra a luta com <b class="hb">⏱ Operador</b> (pelo Andamento, Lutas ou pela chave).</li>
<li>Clique em <b class="hb">🖵 Abrir placar</b> e leve essa janela ao projetor/TV do tatame.</li>
<li>Clique em <b class="hb">▶ Iniciar</b> para disparar o cronômetro. <b>Até esse momento os botões de pontuação ficam desabilitados</b> de propósito, para evitar toques acidentais.</li>
<li>Lance pontos, vantagens e punições com os botões de cada lado.</li>
<li>Clique em <b class="hb">Encerrar luta</b>, escolha o vencedor e o método. O vencedor avança sozinho para a próxima rodada (e o perdedor da semifinal, para o bronze).</li>
</ol>'],
            ['id' => 'operador-botones', 'title' => 'O que faz cada botão', 'body' => '
<ul>
<li><b class="hb">▶ Iniciar</b> / <b class="hb">⏸ Pausar</b>: dispara ou pausa o cronômetro (ele vive no servidor: recarregar a página não o perde).</li>
<li><b class="hb">↺ Reiniciar</b>: volta o cronômetro para a duração completa.</li>
<li><b>Pontos</b>: Queda, Raspagem, Joelho na barriga, Passagem de guarda, Montada e Pegada nas costas somam os pontos configurados (2/2/2/3/4/4 por padrão, editáveis pelo admin).</li>
<li><b class="hb">Vantagem</b> e <b class="hb">Punição</b>: somam 1 ao contador correspondente.</li>
<li><b class="hb">↩ Desfazer</b>: reverte a última ação lançada (pode apertar várias vezes).</li>
<li><b class="hb">Encerrar luta</b>: abre a seleção de vencedor e método (Por pontos, Finalização, Decisão, Desclassificação, W.O.).</li>
</ul>
<p>Os lados do placar são <b>branco</b> e <b>amarelo/verde</b> para distinguir os competidores.</p>'],
            ['id' => 'editar-resultado', 'title' => 'Corrigir uma luta já encerrada', 'body' => '
<p>Se você encerrou uma luta com o vencedor ou método errado, entre de novo no operador dela e clique em <b class="hb">✎ Editar resultado</b>: a luta reabre, você escolhe vencedor e método de novo, e o avanço na chave se corrige sozinho.</p>
<p><b>Restrição</b>: só é possível se a luta seguinte (e a de bronze, se houver) ainda não começou. Se já começou, corrija aquela primeiro.</p>'],
            ['id' => 'fin-torneo', 'title' => 'Quando termina a última luta', 'body' => '
<p>Ao encerrar a última luta do torneio aparece um aviso de "Torneio finalizado!". Automaticamente: o torneio passa a <b>Finalizado</b>, o ranking é recalculado e os certificados são gerados e enviados. Não precisa fazer mais nada.</p>'],
        ]],

        ['id' => 'marcador', 'icon' => 'screen', 'title' => 'Placar para projetar', 'topics' => [
            ['id' => 'marcador-pantalla', 'title' => 'A tela do tatame', 'body' => '
<p>O placar mostra o cronômetro gigante e, para cada competidor: a foto (se enviou), nome, academia, pontos, vantagens e punições. Um lado é branco e o outro metade amarelo / metade verde.</p>
<p>Atualiza sozinho com o que o operador lança — não precisa mexer. Ao encerrar mostra a faixa com o vencedor e o método. Se há publicidade configurada, ela gira em faixas em cima e embaixo.</p>'],
        ]],

        ['id' => 'dashboard-torneo', 'icon' => 'chart', 'title' => 'Aba Dashboard (estatísticas)', 'topics' => [
            ['id' => 'dashboard-stats', 'title' => 'Estatísticas e quadro de medalhas', 'body' => '
<p>Resumo ao vivo do torneio: academia vencedora, quem mais lutou, mais minutos de tatame, mais finalizações, a finalização mais rápida, mais pontos marcados, mais vantagens e mais derrotas; além dos totais de lutas e tempo de tatame, e a divisão de vitórias por método.</p>
<p>O <b>quadro de medalhas por academia</b> soma os ouros, pratas e bronzes de cada academia conforme as divisões vão fechando.</p>'],
        ]],

        ['id' => 'certificados', 'icon' => 'award', 'title' => 'Aba Certificados', 'topics' => [
            ['id' => 'certificados-auto', 'title' => 'Como e quando são gerados', 'body' => '
<p>Os certificados saem sozinhos: cada vez que uma divisão termina, são gerados e enviados por email os do pódio dessa divisão (ouro, prata, bronze) e os de participação de quem ainda não tinha. Não é preciso esperar o fim do torneio.</p>
<p>Cada PDF leva o nome do torneio, o do competidor, sua categoria, a academia, os logos, um selo e um <b>código de verificação</b> único. Em torneios Gi inclui o desenho da faixa; no NoGi mostra a categoria (Amateur / Semi Pro / Pro ou Infantis e juvenis) sem faixa.</p>'],
            ['id' => 'certificados-manual', 'title' => 'O botão Enviar certificados e o download', 'body' => '
<p>O botão <b class="hb">Enviar certificados</b> dispara um lote manual: útil se um email falhou ou se você quer forçar o envio antes da hora. Dá para escolher se inclui pódio e/ou participação. É seguro repetir: nunca reenvia o que já foi enviado.</p>
<p>Na lista de certificados gerados você pode <b class="hb">Baixar</b> cada PDF diretamente.</p>'],
        ]],

        ['id' => 'config-torneo', 'icon' => 'settings', 'title' => 'Aba Configurações do torneio', 'topics' => [
            ['id' => 'link-inscripcion', 'title' => 'Link de inscrição', 'body' => '
<p>No topo está o link público de inscrição. Clique em <b class="hb">Copiar link</b> e compartilhe por WhatsApp, redes ou email: qualquer pessoa com o link pode se inscrever enquanto o torneio estiver em "Inscrições abertas" e houver vaga.</p>'],
            ['id' => 'config-datos', 'title' => 'Dados gerais e status', 'body' => '
<p>Você pode mudar nome, data, limite, logo e modalidade. O campo <b>Status</b> permite forçar manualmente o status do torneio (Rascunho / Inscrições abertas / Em andamento / Finalizado), embora normalmente ele mude sozinho: passa para "Em andamento" na data e para "Finalizado" quando a última luta fecha.</p>'],
            ['id' => 'config-staff', 'title' => 'Equipe do torneio (árbitros e mesa)', 'body' => '
<p>Adicione por email árbitros e mesa de controle (precisam ter conta criada). Eles verão o torneio no painel com acesso para operar chaves, cronômetros e resultados — mas não podem clonar nem excluir o torneio. Com <b class="hb">✕</b> você os remove.</p>'],
            ['id' => 'config-ordenes', 'title' => 'Ordens de luta, idade e peso (com o torneio rodando)', 'body' => '
<p>As mesmas três listas arrastáveis da criação. Mudá-las reordena na hora "Próximas lutas", "Lutas" e "Divisões" — sem tocar chaves nem resultados. Cada uma tem um botão <b class="hb">Usar a ordem geral</b> para voltar ao valor do site.</p>'],
            ['id' => 'config-duracion', 'title' => 'Duração de luta (reaplica ao já criado)', 'body' => '
<p>Ao salvar uma duração nova (por faixa no Gi, por categoria no NoGi), ela se reaplica automaticamente às divisões existentes e suas lutas <b>pendentes</b> — as já disputadas ou ao vivo não mudam.</p>'],
            ['id' => 'config-niveles', 'title' => 'Níveis NoGi (mapeamento faixa → nível)', 'body' => '
<p>Visível apenas em torneios NoGi. Define a que nível (Amateur / Semi Pro / Pro) corresponde cada faixa real. Se você mudar com divisões já geradas, o sistema se acomoda sozinho: cria as divisões novas que faltarem e apaga as que ficaram vazias <b>sem lutas</b>; se uma divisão afetada já tem lutas lançadas, ela é mantida para você resolver manualmente.</p>'],
            ['id' => 'config-clonar-eliminar', 'title' => 'Clonar e excluir o torneio', 'body' => '
<p><b class="hb">⤨ Clonar torneio</b> cria um novo em Rascunho com as mesmas academias, professores e toda a configuração — sem inscritos nem chaves. Ideal para eventos que se repetem. Um admin ainda pode atribuir o clone a outro organizador.</p>
<p>A <b>Zona de risco</b> tem o <b class="hb">Excluir torneio</b>: apaga tudo para sempre (inscritos, chaves, resultados, certificados). Mostra o que você vai perder e pede para digitar o nome exato do torneio para confirmar.</p>'],
        ]],

        ['id' => 'inscribirse', 'icon' => 'user', 'title' => 'Inscrever-se em um torneio (competidor)', 'topics' => [
            ['id' => 'form-inscripcion', 'title' => 'Preencher o formulário', 'body' => '
<ol>
<li>Abra o link de inscrição que o organizador te passou.</li>
<li>Preencha nome, email, gênero, data de nascimento, peso e faixa. A categoria de idade e peso é calculada sozinha com seus dados.</li>
<li>Foto (opcional): aparece no placar das suas lutas e no ranking.</li>
<li>Escolha academia e professor das listas do torneio.</li>
<li>Se você não tem conta, defina uma senha para acompanhar o torneio online.</li>
<li>Envie e <b>confirme o email que chega</b> — sem confirmar, sua inscrição não entra nas chaves.</li>
</ol>'],
            ['id' => 'categoria-o-absoluto', 'title' => 'Categoria, Absoluto ou ambos', 'body' => '
<p>O formulário tem duas caixinhas:</p>
<ul>
<li><b>Categoria</b>: você compete na sua chave normal de idade + peso + faixa (ou nível no NoGi).</li>
<li><b>Absoluto</b>: uma chave sem limite de peso nem idade, todos da mesma faixa/nível juntos.</li>
<li>Você pode marcar <b>as duas</b> e competir nas duas chaves no mesmo dia.</li>
</ul>
<p>O Absoluto não está disponível para infantis, juvenis, faixa branca (Gi) nem nível Amateur (NoGi) — a caixinha se desabilita sozinha nesses casos.</p>'],
            ['id' => 'seguir-torneo', 'title' => 'Acompanhar suas lutas durante o torneio', 'body' => '
<p>Entre com seu email e senha: no <b>Meu painel</b> você vê seu próximo adversário, os resultados das suas lutas e o botão para ver sua posição na chave em tempo real. Quando sua divisão termina, seu certificado chega por email.</p>'],
        ]],

        ['id' => 'rankings', 'icon' => 'chart', 'title' => 'Rankings', 'topics' => [
            ['id' => 'rankings-uso', 'title' => 'Abas, filtros e como os pontos são calculados', 'body' => '
<p>Há dois rankings separados: <b>Gi</b> e <b>NoGi</b> (abas no topo) — cada torneio soma apenas ao da sua modalidade.</p>
<ul>
<li>Filtros: gênero, idade e peso. No Gi também por faixa; no NoGi por categoria (Infantis/Juvenis, Amateur, Semi Pro, Pro).</li>
<li>Pontos (configuráveis pelo admin): ouro 9, prata 3, bronze 1, vitória 2 e +1 por finalização. Se alguém compete em Categoria e Absoluto, soma o pódio das duas chaves.</li>
<li>A identidade do competidor é o email: os pontos acumulam entre torneios.</li>
</ul>'],
        ]],

        ['id' => 'administracion', 'icon' => 'sliders', 'title' => 'Administração (somente admin)', 'topics' => [
            ['id' => 'admin-config', 'title' => 'Configurações gerais do site', 'body' => '
<p>Em <b>Administração → Configurações</b> se definem os padrões de todo o site (cada torneio pode depois sobrescrevê-los):</p>
<ul>
<li><b>Nome do site</b>, <b>torneios por semana</b> por organizador, e a <b>retenção em meses</b> para excluir torneios antigos automaticamente (0 = nunca).</li>
<li><b>Ordens de luta</b> Gi e NoGi, ordem por idade e por peso (listas arrastáveis).</li>
<li><b>Durações de luta</b> Gi (por faixa) e NoGi (por categoria), e os <b>cortes de idade</b> infantil/juvenil.</li>
<li><b>Níveis NoGi</b>: o mapeamento geral faixa → Amateur/Semi Pro/Pro.</li>
<li><b>SMTP</b>: o servidor de email de saída, com o botão <b class="hb">Enviar teste</b> para verificar que funciona.</li>
<li><b>Pontuação de ações</b> do placar (queda, raspagem, etc.) e <b>pontuação do ranking</b> (ouro/prata/bronze/vitória/finalização).</li>
</ul>'],
            ['id' => 'admin-usuarios', 'title' => 'Usuários', 'body' => '
<p>Cadastro, edição e exclusão de usuários, com função <b>user</b> (organizador) ou <b>admin</b>. Daqui também dá para verificar o email de um usuário manualmente. Um admin não pode excluir a si mesmo.</p>'],
            ['id' => 'admin-publicidad', 'title' => 'Publicidade', 'body' => '
<p>Os anúncios giram nas telas projetadas (chaves e placares). Cada anúncio pode ser <b>texto</b> ou <b>imagem/banner</b>, com duração em segundos e animação (carrossel, fade, zoom ou faixa contínua). O alcance pode ser <b>geral</b> (todos os torneios) ou de <b>um torneio específico</b>; e cada torneio define em "Publicidade por torneio" que mistura usa (gerais + próprios, só próprios, só gerais ou nenhum).</p>'],
            ['id' => 'admin-schedulers', 'title' => 'Schedulers / Cron', 'body' => '
<p>Lista as tarefas agendadas com a última execução e um botão <b class="hb">▶ Executar agora</b> para dispará-las manualmente: <b>emails</b> (fila de correio), <b>certificates</b> (lotes de certificados pendentes), <b>rankings</b> (recálculo), <b>tournament_status</b> (passa torneios para "Em andamento"/"Finalizado"), <b>cleanup</b> (limpa inscrições não verificadas e emails antigos) e <b>delete_old_tournaments</b> (exclui torneios mais antigos que a retenção configurada). Abaixo estão as linhas prontas para colar no crontab do servidor.</p>'],
        ]],
    ],
];
