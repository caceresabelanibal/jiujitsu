Certificados TLS (HTTPS) — NO se versionan en git (ver .gitignore).

Poné acá dos archivos con estos nombres exactos:

  taninzu.crt   El certificado del dominio SEGUIDO del/los intermedio(s), en
                un solo archivo (esto se llama "fullchain"). Primero el bloque
                del certificado del dominio (el CRT que te da DonWeb), y debajo
                el certificado intermedio (CA Intermediate).

  taninzu.key   La clave privada (el bloque BEGIN PRIVATE KEY).

Cómo armar el fullchain (en el server, dentro de la carpeta certs/):

  1) Pegá el certificado del dominio en  taninzu.crt
  2) Descargá el "Certificado intermedio (CA Intermediate)" de DonWeb
     (por ej. como  intermediate.crt )
  3) Concatenalos en ese orden:
       cat dominio.crt intermediate.crt > taninzu.crt
     (dominio primero, intermedio después)

Poné la clave privada en  taninzu.key  y protegé el archivo:
       chmod 600 taninzu.key

Con eso, levantá con el override TLS:
  docker compose -f docker-compose.yml -f docker-compose.prod.yml -f docker-compose.tls.yml up -d --build
