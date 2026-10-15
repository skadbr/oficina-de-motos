# Oficina de motos

Sistema em PHP puro, organizado em MVC, com SQLite. O banco fica em `data/oficina.sqlite`, dentro da pasta do projeto e fora da pasta pública; assim os cadastros acompanham o pendrive mesmo que a letra da unidade mude.

## Preparar no Windows

1. Copie a pasta inteira para o pendrive.
2. No primeiro uso, conecte à internet e execute `PREPARAR_PENDRIVE.bat`. Ele baixa o PHP oficial x64 para `runtime/php` no próprio pendrive.
3. Execute `INICIAR.bat` e acesse `http://127.0.0.1:8000`.

Nos próximos usos, basta conectar o pendrive e abrir `INICIAR.bat`. O programa roda localmente no computador, sem enviar os dados para a internet. Feche a janela do servidor com Ctrl+C antes de remover o pendrive; faça cópias de segurança da pasta `data`.

O computador precisa ser Windows x64 e pode precisar do Microsoft Visual C++ Redistributable 2015-2022 x64, dependência das distribuições oficiais de PHP para Windows. Se já houver PHP com PDO SQLite no PATH, o iniciador também pode usá-lo.

## MVC

- `app/Controllers`: recebe as ações e prepara a resposta.
- `app/Models`: consultas, gravações e regras de persistência.
- `app/Views`: interface HTML.
- `app/Core/Database.php`: conexão e criação do esquema SQLite.
- `public/index.php`: ponto de entrada servido pelo PHP.

## Funcionalidades

- Cadastro e consulta de clientes, motos e serviços.
- OS relacionada ao cliente e à moto desse cliente, com serviços executados.
- Data de criação, conclusão opcional ou fechamento posterior com data de término.
- Descrição e valor das peças, valor da mão de obra e total calculado.