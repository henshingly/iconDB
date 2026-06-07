# iconDB

Ein  Datenbank für Mannschaftswappen. 

### Systemvoraussetzungen
- PHP 8.x
- MySQL

### Installation
Beim Erstaufruf erfolgt ein automatisierter Aufruf der Installation. Anzugeben sind hier die Credentials für die Datenbank als auch die zu nutzenden Grafiktypen.

### Administration
Die Administration kann über `/adminer/` aufgerufen werden, es gibt kein Passwort für den Administrationsbereich. Ggf. das Verzeichnis umbenennen.

### Massenimport bestehender Wappen
Mittels `/import/dir2base.php` können im Verzeichnis `/icons/` bereits vorab abgelegte Wappen in die Datenbank importiert werden.


# iconDB

A database for team logos

### System requirements
- PHP 8.x
- MySQL

### Installation
On the first call the iinstallation starts automatically. You have to enter the credentials of the database as well as the image types to use.

### Administration
The administration ist accessable through `/adminer/`, no password needed. Might change the directory name.

### Massemport existing logos
By using `/import/dir2base.php` the images in the directory `/icons/` are inserted into the database.
