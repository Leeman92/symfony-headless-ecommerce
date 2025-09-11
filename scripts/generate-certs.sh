#!/bin/bash

# Generate self-signed certificates for local development
set -e

CERT_DIR="docker/traefik/certs"
DOMAIN="ecommerce.localhost"

echo "Generating self-signed certificates for *.${DOMAIN}..."

# Create certificate directory
mkdir -p "$CERT_DIR"

# Create OpenSSL config file
cat > "$CERT_DIR/openssl.conf" <<EOF
[req]
default_bits = 2048
prompt = no
default_md = sha256
distinguished_name = dn
req_extensions = v3_req

[dn]
C=US
ST=Dev
L=Local
O=Ecommerce
CN=*.${DOMAIN}

[v3_req]
basicConstraints = CA:FALSE
keyUsage = critical, digitalSignature, keyEncipherment
extendedKeyUsage = serverAuth
subjectAltName = @alt_names

[alt_names]
DNS.1 = ${DOMAIN}
DNS.2 = *.${DOMAIN}
DNS.3 = traditional.${DOMAIN}
DNS.4 = traefik.${DOMAIN}
DNS.5 = localhost
IP.1 = 127.0.0.1
EOF

# Generate private key
openssl genrsa -out "$CERT_DIR/${DOMAIN}.key" 2048

# Generate certificate signing request
openssl req -new -key "$CERT_DIR/${DOMAIN}.key" -out "$CERT_DIR/${DOMAIN}.csr" -config "$CERT_DIR/openssl.conf"

# Generate self-signed certificate
openssl x509 -req -in "$CERT_DIR/${DOMAIN}.csr" -signkey "$CERT_DIR/${DOMAIN}.key" -out "$CERT_DIR/${DOMAIN}.crt" -days 365 -extensions v3_req -extfile "$CERT_DIR/openssl.conf"

# Clean up temporary files
rm "$CERT_DIR/${DOMAIN}.csr" "$CERT_DIR/openssl.conf"

echo "Certificates generated successfully!"
echo "Certificate: $CERT_DIR/${DOMAIN}.crt"
echo "Private Key: $CERT_DIR/${DOMAIN}.key"
echo ""
echo "Note: You may need to accept the self-signed certificate in your browser."