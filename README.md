# iconDB

Ein Datenbank für Mannschaftswappen. 

### Systemvoraussetzungen
- PHP 8.x
- MySQL

### Installation
Beim Erstaufruf erfolgt ein automatisierter Aufruf der Installation. Anzugeben sind:
- **Datenbank-Credentials**: Host, Benutzer, Passwort, Datenbankname
- **Admin-Anmeldedaten**: Benutzername und Passwort für den geschützten Administrationsbereich

Die Installation erzeugt automatisch die notwendigen `.htaccess` und `.htpasswd` Dateien für den Verzeichnisschutz des Admin-Bereichs.

### Administration
Die Administration kann über `/adminer/` aufgerufen werden. Der Administrationsbereich ist durch HTTP Basic Authentication geschützt. Die Anmeldedaten werden während der Installation festgelegt.

### Massenimport bestehender Wappen
Mittels `/import/dir2base.php` können im Verzeichnis `/icons/` bereits vorab abgelegte Wappen in die Datenbank importiert werden.

---

# iconDB

A database for team logos

### System requirements
- PHP 8.x
- MySQL

### Installation
On the first call the installation starts automatically. You have to enter:
- **Database credentials**: Host, user, password, database name
- **Admin credentials**: Username and password for the protected admin area

The installation automatically creates the necessary `.htaccess` and `.htpasswd` files for directory protection of the admin area.

### Administration
The administration is accessible through `/adminer/`. The admin area is protected by HTTP Basic Authentication. The login credentials are set during installation.

### Mass import existing logos
By using `/import/dir2base.php` the images in the directory `/icons/` are inserted into the database.
