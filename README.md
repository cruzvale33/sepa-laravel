# 📦 SepaLaravel

**SepaLaravel** es un paquete PHP/Laravel para generar archivos XML de transferencias SEPA (Single Euro Payments Area) de manera sencilla y automática. Este paquete soporta: Presentadores (`Presenter`), Acreedores (`Creditor`), Deudores (`Debtor`), Pagos (`Payment`), y la generación de XML listo para enviar a entidades bancarias. ⚡ Instalación rápida, sin configuración manual gracias a Laravel Package Auto-Discovery. 

## ✨ Instalación

Requiere **PHP 8.0+** y **Laravel 8, 9 o 10**. Instala el paquete vía Composer:

```bash
composer require cruzvale33/sepa-laravel

📚 Documentación
Presenter: Define los datos de la empresa que envía el XML.

Creditor: Define los datos del acreedor de las domiciliaciones.

Debtor: Define los datos del cliente deudor.

Payment: Define cada uno de los cobros o recibos que se quieren realizar.

La librería se encarga de generar automáticamente el XML en formato SEPA cumpliendo las normativas.

🛠️ Testing
bash
Copy
Edit
composer test
(Próximamente incluiremos un set de tests automatizados.)

📄 Licencia
Este paquete está licenciado bajo la MIT License.

💬 Soporte
¿Tienes dudas, errores o sugerencias? Abre un Issue en GitHub o envía un Pull Request con mejoras. ¡Toda contribución es bienvenida! 🚀

Creado con ❤️ por @cruzvale33