# Sistema Portável para Oficina de motos

Sistema em PHP puro, organizado em MVC, com SQLite. O banco fica em `data/oficina.sqlite`, dentro da pasta do projeto e fora da pasta pública; assim os cadastros acompanham o pendrive mesmo que a letra da unidade mude.

## Preparar no Windows 10 ou no Windows 11.

1. Copie a pasta inteira para o pendrive.
2. No primeiro uso, conecte à internet e execute `PREPARAR_PENDRIVE.bat`. Ele baixa o PHP oficial x64 para `runtime/php` no próprio pendrive.
3. Execute `INICIAR.bat` e acesse `http://127.0.0.1:8000`.

Nos próximos usos, basta conectar o pendrive e abrir `INICIAR.bat`. O programa roda localmente no computador, sem enviar os dados para a internet. Feche a janela do servidor com Ctrl+C antes de remover o pendrive; faça cópias de segurança da pasta `data`.

O computador precisa ser Windows x64 e pode precisar do Microsoft Visual C++ Redistributable 2015-2022 x64, dependência das distribuições oficiais de PHP para Windows. Se já houver PHP com PDO SQLite no PATH, o iniciador também pode usá-lo.

## Sugestão de segurança: BitLocker To Go

Criptografe o pendrive para proteger o banco SQLite, os backups e os demais arquivos em caso de perda. O BitLocker To Go para ativação normalmente está disponível nas edições Windows Pro, Enterprise e Education; alguns computadores com Windows Home oferecem Criptografia do dispositivo, que é um recurso diferente.

### Ativar

1. Faça uma cópia de segurança dos arquivos importantes e conecte o pendrive.
2. No Explorador de Arquivos, abra **Este Computador**, clique com o botão direito na unidade USB e escolha **Ativar BitLocker**. Se a opção não aparecer, procure **Gerenciar BitLocker** no menu Iniciar e verifique **Unidades de dados removíveis**.
3. Escolha desbloquear a unidade com uma senha e crie uma senha forte.
4. Salve a chave de recuperação em outro local seguro. Não a guarde no mesmo pendrive.
5. Para um pendrive que já contém arquivos, escolha criptografar a unidade inteira. Escolha o modo compatível para facilitar o uso em outros computadores Windows.
6. Inicie a criptografia e aguarde a conclusão antes de remover o pendrive.

### Usar e descriptografar

1. Para usar o sistema, conecte o pendrive, abra a unidade e informe a senha. Se não lembrar a senha, use a chave de recuperação.
2. Ao terminar, ejete o pendrive com segurança. Desbloqueá-lo para uso não remove a criptografia.
3. Para remover permanentemente a proteção, desbloqueie a unidade, abra **Gerenciar BitLocker**, escolha **Desativar BitLocker** para o pendrive e aguarde a descriptografia terminar.

Desativar o BitLocker deixa todos os arquivos legíveis para quem tiver o pendrive. Mantenha a criptografia ativada para proteção contra perda do dispositivo. Backups salvos fora do pendrive criptografado precisam de proteção própria.

## MVC

- `app/Controllers`: recebe as ações e prepara a resposta.
- `app/Models`: consultas, gravações e regras de persistência.
- `app/Views`: interface HTML.
- `app/Core/Database.php`: conexão e criação do esquema SQLite.
- `public/index.php`: ponto de entrada servido pelo PHP.

## Funcionalidades

- Cadastro e edição de clientes e motocicletas, com OS vinculadas.
- Cadastro de peças com descrição, quantidade em estoque e preço de venda, incluindo busca, edição e exclusão protegida pelo histórico da OS.
- Lista de motocicletas com pesquisa, ordenação e paginação local, sem dependências externas ou acesso à internet.
- Na OS, peças são pesquisadas no estoque, adicionadas com quantidade e preço; o subtotal é calculado e o estoque baixado ao salvar. Cancelar a OS devolve as peças, e saldo insuficiente impede a gravação.
- O histórico permite abrir cada OS em uma folha de impressão com dados do cliente, moto, serviços, peças, valores, status e datas.
- Serviços executados são descritos livremente em cada OS; o usuário informa diretamente o valor da mão de obra.
- Data e hora de término são registradas automaticamente ao faturar ou cancelar a OS.
- O catálogo de serviços foi substituído por texto livre; nomes de OS antigas são migrados para a descrição. Novos bancos não criam as tabelas do catálogo; bancos existentes mantêm os dados legados sem usá-los.
- A tela Financeiro filtra por intervalo usando criação para OS abertas, faturamento para OS faturadas e cancelamento para OS canceladas; exibe quantidade e valor total por status e as ordens do período. Cancelamentos antigos sem data registrada usam a data de criação como referência.
- Datas digitadas no Financeiro e nas ordens de serviço usam o formato brasileiro `dd/mm/aaaa`; horários são informados separadamente.
- Em `Utilidades`, escolha uma pasta para salvar um snapshot SQLite ou selecione uma pasta de backups para restaurar um arquivo válido. Restaurações substituem os dados atuais após validação.