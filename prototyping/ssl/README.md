# Demo SSL Server

This is a demonstration SSL Server. It needs three files, residing in the same directory:

 * ca.crt - your certificate authority
 * server.crt - the certificate file, signed by ca.crt
 * server.key - the private key belonging to server.key

The server is just called using ssl-server.php and listens on 0.0.0.0:4096.
The client is called using ssl-client.php <hostname>. It is basically an echoing server; use 'quit' to disconnect and end the client.
