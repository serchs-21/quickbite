# Documentación del Proyecto — QuickBite

Este documento reúne la estructura de la base de datos, los pasos para levantar el proyecto en entornos locales como **XAMPP** o **Laragon**, y las cuentas listas para probar el sistema.

**Desarrollado por:** Sergio & Kenia  
**Carrera:** Ingeniería en Sistemas Computacionales — 7mo Semestre Escolarizado (U.A. Cuichapa)

---

## 1. Diagrama Entidad-Relación (Base de Datos)

La base de datos `quickbite` está diseñada en MariaDB/MySQL para gestionar el catálogo de la cafetería, el registro de clientes y el seguimiento de cada orden desde que se solicita hasta que se entrega.

```mermaid
erDiagram
    USUARIOS {
        int id PK
        varchar nombre
        varchar email
        varchar password
        enum rol "gerente | cliente"
        datetime fecha_registro
    }

    PRODUCTOS {
        int id PK
        varchar nombre
        text descripcion
        decimal precio
        varchar categoria
        varchar imagen
        tinyint disponible
    }

    PEDIDOS {
        int id PK
        int usuario_id FK
        decimal total
        varchar estado "pendiente | preparando | listo | entregado"
        text notas
        datetime fecha_pedido
    }

    DETALLE_PEDIDOS {
        int id PK
        int pedido_id FK
        int producto_id FK
        int cantidad
        decimal precio_unitario
        decimal subtotal
    }

    USUARIOS ||--o{ PEDIDOS : "realiza"
    PEDIDOS ||--|{ DETALLE_PEDIDOS : "contiene"
    PRODUCTOS ||--o{ DETALLE_PEDIDOS : "se incluye en"