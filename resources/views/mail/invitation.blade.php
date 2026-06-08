<x-mail::message>
# Olá, {{ $name }}

Você foi convidado para acessar o **{{ $clinic }}** no Atma.

Clique no botão abaixo para definir sua senha e ativar seu acesso.

<x-mail::button :url="$url">
Aceitar convite
</x-mail::button>

Este convite expira em 7 dias. Se você não esperava este e-mail, pode ignorá-lo com segurança.

Atenciosamente,<br>
{{ $clinic }}
</x-mail::message>
