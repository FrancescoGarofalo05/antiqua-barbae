# 💈 Antiqua Barbae - E-commerce per Barberia

**Un e-commerce completo per la vendita di prodotti da barba artigianali.**

Sviluppato come progetto full-stack per dimostrare competenze in PHP, MySQL, JavaScript, sicurezza delle transazioni e integrazione con gateway di pagamento.

![Antiqua Barbae](https://via.placeholder.com/800x400/8B4513/ffffff?text=Antiqua+Barbae+Shop)

---

## 🚀 Caratteristiche Principali

### Area Pubblica (Clienti)

- 🏠 **Homepage** con hero e prodotti in evidenza
- 🛍️ **Shop** con catalogo prodotti e filtro per categoria
- 📄 **Pagina prodotto** con dettagli, immagini e pulsante acquisto
- 🛒 **Carrello persistente** (LocalStorage) - rimane dopo il refresh
- 💳 **Checkout** con form cliente e calcolo totale lato server (anti-manipolazione)
- 🔐 **Simulazione pagamento** con Stripe/PayPal (fake-payment)
- ✅ **Pagina di successo** dopo il pagamento
- 📧 **Webhook simulato** per conferma pagamento server-to-server

### Area Riservata (Proprietario Barberia)

- 🔐 **Doppia registrazione:**
  - **Proprietario:** con Partita IVA (validazione leggera)
  - **Cliente:** registrazione libera per acquistare
- 📊 **Dashboard** con statistiche (prodotti, ordini, incassi)
- 🧴 **CRUD Prodotti:** aggiungi, modifica, elimina prodotti con immagini
- 📦 **Gestione Ordini:** visualizza, filtra, cambia stato (pending/paid/cancelled)
- 📱 **Completamente responsive** con menu mobile

### Sicurezza Implementata

| Minaccia                    | Protezione                     |
| --------------------------- | ------------------------------ |
| **SQL Injection**           | Prepared Statements PDO        |
| **XSS**                     | `htmlspecialchars()` su output |
| **Password**                | Hashing con `password_hash()`  |
| **Session Fixation**        | `session_regenerate_id()`      |
| **Manipolazione prezzi**    | Ricalcolo totale lato server   |
| **Accesso non autorizzato** | Controllo sessione e ruoli     |

---

## 🛠️ Tecnologie Utilizzate

| Tecnologia           | Utilizzo                 |
| -------------------- | ------------------------ |
| **PHP 8+**           | Backend, logica business |
| **MySQL**            | Database relazionale     |
| **PDO**              | Connessione sicura       |
| **HTML5/CSS3**       | Frontend responsive      |
| **JavaScript (ES6)** | Carrello, interattività  |
| **LocalStorage**     | Persistenza carrello     |
| **XAMPP**            | Ambiente di sviluppo     |

---

## 📁 Struttura del Progetto

antiqua-barbae/
├── index.php # Homepage
├── shop.php # Catalogo
├── product.php # Dettaglio prodotto
├── cart.php # Carrello
├── checkout.php # Checkout
├── fake-payment.php # Simulazione pagamento
├── webhook.php # Endpoint conferma
├── success.php # Pagina successo
├── admin/
│ ├── register.php # Registrazione (doppia)
│ ├── login.php # Login
│ ├── logout.php # Logout
│ ├── dashboard.php # Dashboard proprietario
│ ├── products/
│ │ ├── list.php # Lista prodotti
│ │ ├── create.php # Aggiungi (con upload immagine)
│ │ ├── edit.php # Modifica
│ │ └── delete.php # Elimina
│ └── orders/
│ ├── list.php # Lista ordini
│ └── detail.php # Dettaglio ordine
├── includes/
│ ├── config.php # Connessione database
│ ├── header.php # Header pubblico (con logo)
│ ├── footer.php # Footer pubblico
│ ├── auth.php # Funzioni autenticazione
│ └── functions.php # Funzioni helper
├── assets/
│ ├── css/
│ │ └── style.css # Stili responsive
│ ├── js/
│ │ ├── main.js # JavaScript generale
│ │ └── cart.js # Logica carrello
│ └── images/
│ ├── logo.png # Logo del sito (manuale)
│ ├── hero-bg.jpg # Sfondo hero (manuale)
│ └── .gitkeep # Mantiene la cartella su Git
├── logs/
│ ├── payments.log # Log pagamenti (auto-generato)
│ └── .gitkeep # Mantiene la cartella su Git
├── .htaccess # Configurazione Apache
├── .gitignore # Esclusioni Git
├── database.sql # Struttura database
├── LICENSE # Licenza MIT
└── README.md # Documentazione

text

---

## 🗄️ Struttura Database

### Tabelle principali:

- **barberias** - Dati della barberia (nome, indirizzo, telefono)
- **users** - Utenti (owner/customer) con Partita IVA per owner
- **categories** - Categorie prodotti (Oli, Cere, Kit, Accessori)
- **products** - Prodotti in vendita con immagini
- **orders** - Ordini ricevuti
- **order_items** - Dettaglio prodotti per ordine

---

## 🔑 Flusso di Pagamento (Simulato)

1. Cliente aggiunge prodotti al carrello (LocalStorage)
2. Checkout: inserisce dati e crea ordine (stato `pending`)
3. Reindirizzamento a `fake-payment.php` (simula Stripe)
4. "Pagamento" → chiama `webhook.php` che:
   - Verifica la richiesta
   - Aggiorna ordine a `paid`
   - Scala lo stock dei prodotti
5. Reindirizza a `success.php`

**In produzione:** `fake-payment.php` sarebbe sostituito dal vero gateway Stripe/PayPal.

---

## 🚀 Installazione Locale

1. Clona il repository in `C:\xampp\htdocs\antiqua-barbae\`
2. Avvia Apache e MySQL da XAMPP
3. Crea database `antiqua_db` e importa `database.sql`
4. Configura `includes/config.php` con le tue credenziali
5. Assicurati che le cartelle `assets/images/` e `logs/` abbiano permessi di scrittura
6. Accedi: `http://localhost/antiqua-barbae/`

### Credenziali Demo

| Ruolo        | Email                    | Password |
| ------------ | ------------------------ | -------- |
| Proprietario | (registrati con P.IVA)   | -        |
| Cliente      | (registrati liberamente) | -        |

---

## 🖼️ Gestione Immagini

### Immagini Manuali (da inserire a mano)

| File          | Percorso                    | Descrizione                               |
| ------------- | --------------------------- | ----------------------------------------- |
| `logo.png`    | `assets/images/logo.png`    | Logo del sito (200x200 px consigliato)    |
| `hero-bg.jpg` | `assets/images/hero-bg.jpg` | Sfondo homepage (1920x800 px consigliato) |

### Immagini Prodotti (caricate via Dashboard)

Le immagini dei prodotti vengono caricate dal proprietario tramite il form di creazione/modifica prodotto. Il sistema:

- Rinomina automaticamente il file (es. `65f3a2b1c8d9e.jpg`)
- Ridimensiona automaticamente via CSS (`max-height: 200px`)
- Salva in `assets/images/`

---

## ⚠️ Note Importanti

- **Partita IVA:** Validazione leggera (11 cifre). In produzione usare API dell'Agenzia delle Entrate.
- **Pagamento:** Simulato. In produzione integrare Stripe/PayPal.
- **Privacy/Cookie:** I link nel footer sono placeholder. In produzione compilare con testi legali.
- **Log:** La cartella `logs/` contiene `payments.log` con lo storico dei pagamenti.

---

## 🔮 Miglioramenti Futuri

- [ ] Integrazione reale Stripe/PayPal
- [ ] Invio email di conferma ordine
- [ ] Recupero password
- [ ] Recensioni prodotti
- [ ] Gestione stock avanzata
- [ ] API REST

---

## 👨‍💻 Autore

**Francesco Garofalo**  
Web Developer Full-Stack

- 📧 Email: [francescogarofalo34@gmail.com](mailto:francescogarofalo34@gmail.com)

---

## 📄 Licenza

Questo progetto è rilasciato sotto licenza **MIT**. Vedi il file [LICENSE](./LICENSE) per i dettagli.

---

_Realizzato con 💈 e 💻 per il portfolio_
