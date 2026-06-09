<x-mail::message>
# Olá, {{ $patientName }}

A clínica **{{ $clinic }}** enviou um documento para a sua assinatura:

**{{ $documentTitle }}**

Clique no botão abaixo para visualizar o documento e assiná-lo.

<x-mail::button :url="$url">
Visualizar e assinar
</x-mail::button>

Se você não esperava este e-mail, pode ignorá-lo com segurança.

Atenciosamente,<br>
{{ $clinic }}
</x-mail::message>
