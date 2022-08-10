# Integración oficial de Pago46 con Magento 2 (Versión 1.0.1)

Esta extensión permite integrar una tienda Magento 2 con el medio de pago Pago46.  Para obtener más información acerca de Pago46 dirijase a https://www.pago46.com

## Requisitos

Debe tener una tienda Magento 2.3.x o 2.4.x

## Instalación

Se puede descargar el código y copiarlo en app/Code/Pago46/Cashin o bien por composer configurando este repositorio dentro del composer.json.

Luego se deben ejecutar los comandos clásicos para instalar cualquier módulo en Magento 2.

## Configuración

1. Diríjase a ** Stores -> Configuration -> Sales -> Payment Method **
2. Luego localice el medio Pago46 y haga click en el botón ** Configure **
3. Haga click en ** Payment Settings ** 
4. Configure los siguientes campos:

** Enable: ** (Yes/No) - Para habilitar o deshabilitar el medio de pago.

** Title: ** - Título que verá el cliente en el checkout.

** Order Status after Payment Confirmation: ** Estado al que pasa el pedido una vez que se confirma el pago.

** Merchant Country Code: ** País de recaudación de la tienda.

** Order Timeout: ** Tiempo de validez del pago en minutos.  Una vez que transcurra ese tiempo el pedido se cancelará automáticamente.

** Merchant Key y Merchant Secret: ** Son las credenciales provistas por Pago46 cuando se da de alta la cuenta.

** Test mode (use sandbox): ** (Yes/No): Para usar el entorno de pruebas de Pago46.
