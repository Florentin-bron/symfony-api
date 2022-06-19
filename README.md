
# SecurytiAPI: Api TODO Symfony avec des failles de sécurités

Une petite API Todo avec pleins de petits problèmes 🙂

Rien ne va même le nom de l'api...


## Installation

Prérequis : 
- composer 2.2.9
- MySql 5.7.36
- PhpMyAdmin 5.1.1
- Php 8.0.13
- Postman


et installation :
```bash
  composer install
```
+ penser à changer les config dans le .env pour la BDD

La liste de toutes les routes disponibles sont à la racine dans le fichier : "postman_collection.json"
et la BDD aussi sous le nom : "securytiapi.sql" (oui securyti pas security)
## Utilisation

Pour lancer le serveur local

```
symfony server:start
```
Puis utiliser Postman pour les appels API

Exemple de route:
- Récupérer ma liste de Todo
```
127.0.0.1:8000/api/todos/{jwt_of_user}
```

### L'utilisation est simple:
- Création d'un utilisateur
- Un récupère le JWT à la création
- On utilise ce JWT dans les routes qui le nécessite
- On profite de l'API (création / modif / supression des todo)