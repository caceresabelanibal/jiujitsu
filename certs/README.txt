Certificados TLS (HTTPS) — NO se versionan en git (por eso no están acá).
La clave privada es secreta y este repo es público: nunca la subas a git.

=================  PUESTA EN MARCHA (una sola vez en el server)  =================

1) Copiá la plantilla de entorno a .env (habilita el comando corto con HTTPS):
       cp .env.prod.example .env

2) Creá la clave privada:
       nano certs/taninzu.key
   pegá adentro tu bloque  -----BEGIN PRIVATE KEY----- ... -----END PRIVATE KEY-----
   guardá y luego:
       chmod 600 certs/taninzu.key

3) Creá el certificado del dominio:
       nano certs/dominio.crt
   pegá adentro tu bloque  -----BEGIN CERTIFICATE----- ... -----END CERTIFICATE-----

4) Bajá el certificado intermedio de Sectigo y armá el fullchain
   (el dominio primero, el intermedio después):
       curl -fsSL http://crt.sectigo.com/SectigoPublicServerAuthenticationCADVR36.crt \
         | openssl x509 -inform DER -out certs/intermediate.pem
       cat certs/dominio.crt certs/intermediate.pem > certs/taninzu.crt

   (Alternativa: bajá el "Certificado intermedio (CA Intermediate)" desde el panel
    de DonWeb, guardalo como certs/intermediate.pem y corré solo el "cat" de arriba.)

5) Levantá:
       docker compose up -d --build

=================  DE ACÁ EN MÁS  =================

Actualizar y relanzar es solo:
       git pull
       docker compose up -d --build

Los certificados quedan en el server (git no los toca). Solo hay que rehacer el
paso 2-4 cuando renueves/reemitas el certificado.
