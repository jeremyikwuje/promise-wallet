sudo mkdir -p /var/www/account-api.forward.africa/html
sudo chown -R $USER:$USER /var/www/account-api.forward.africa/html
sudo chmod -R 755 /var/www/account-api.forward.africa
nano /var/www/account-api.forward.africa/html/index.html

<html>
    <head>
        <title>Welcome to account-api.forward.africa!</title>
    </head>
    <body>
        <h1>Success! The account-api.forward.africa server block is working!</h1>
    </body>
</html>

sudo nano /etc/nginx/sites-available/account-api.forward.africa

server {
        listen 80;
        listen [::]:80;

        root /var/www/account-api.forward.africa/html;
        index index.html index.htm index.nginx-debian.html;

        server_name account-api.forward.africa.com;

        location / {
                try_files $uri $uri/ =404;
        }
}

sudo ln -s /etc/nginx/sites-available/account-api.forward.africa /etc/nginx/sites-enabled/
sudo nano /etc/nginx/nginx.conf
sudo nginx -t
sudo systemctl restart nginx