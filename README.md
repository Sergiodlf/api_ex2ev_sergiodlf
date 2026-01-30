# API EX2EV SERGIO DE LA FUENTE

Este es el repositorio para el examen de Desarrollo web de la segunda evaluacion.
Consta de una api para un gym4v.

## Instalación

1. **Ubícate en tu workspace**
2. **Clona el repositorio**
    ```bash
    git clone https://github.com/Sergiodlf/api_ex2ev_sergiodlf.git
    ```
3. **Abre una terminal desde dentro de la carpeta principal del proyecto e instala las dependencias**
   ```bash
   composer install
   ```
4. **Inicia MySQL desde xampp**
5. **Crea la base de datos**
   ```bash
   php bin/console doctrine:database:create
   ```
6. **Crea migraciones**
   ```bash
   php bin/console make:migration
   php bin/console doctrine:migrations:migrate
   ```
8. **Carga los datos iniciales**
   ```bash
   php bin/console doctrine:fixtures:load
   ```
9. **Lanza la api**
   ```bash
   symfony serve
   ```
10. **Comprueba que funciona desde el navegador:** (deberían aparecen las actividades de los datos iniciales)
   ```bash
   localhost:8000/activities
   ```


## Datos de prueba

Hay unos datos de prueba que se pueden cargar con un comando especificado anteriormente.
Se crearán 10 clientes y 10 actividades con canciones, pero sin reservas.

## Un cliente no se puede apuntar en la misma actividad 2 veces

Aunque no lo especifica ni el yaml ni el enunciado, se ha añadido la validación para que un mismo cliente no se pueda apuntar en la misma actividad 2 veces.