sudo mkdir -p /var/www/usdt-api.forward.africa/html
sudo chown -R $USER:$USER /var/www/usdt-api.forward.africa/html
sudo chmod -R 755 /var/www/usdt-api.forward.africa
nano /var/www/usdt-api.forward.africa/html/index.html

<html>
    <head>
        <title>Welcome to usdt-api.forward.africa!</title>
    </head>
    <body>
        <h1>Success! The usdt-api.forward.africa server block is working!</h1>
    </body>
</html>

sudo nano /etc/nginx/sites-available/usdt-api.forward.africa

server {
        listen 80;
        listen [::]:80;

        root /var/www/usdt-api.forward.africa/html;
        index index.html index.htm index.nginx-debian.html;

        server_name usdt-api.forward.africa.com;

        location / {
                try_files $uri $uri/ =404;
        }
}

sudo ln -s /etc/nginx/sites-available/usdt-api.forward.africa /etc/nginx/sites-enabled/
sudo nano /etc/nginx/nginx.conf
sudo nginx -t
sudo systemctl restart nginx