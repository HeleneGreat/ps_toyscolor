# A faire lors du déploiement en production !!

- thème/logo => configurer la disposition des pages : tout en fullwidth
- Ajouter la feuille de style compilée :
  classes/controller/FrontController/SetMedia()
  $this->registerStylesheet('theme-style', '/assets/css/style.css', ['media' => 'all', 'priority' => 5000]);



### Mode débug
config/defines.inc.php
/* Debug only */
if (!defined('_PS_MODE_DEV_')) {
define('_PS_MODE_DEV_', true);
}



## Ce que j'ai fait :
- ajout du cdn fontawesome dans le head.tpl
- création du module hc_shoppresentation
- ajout du module best-sellers
- création du module hc_mainproduct


## Modifications depuis le 1er import sur CPanel



# TODO
- vérifier tous les liens des boutons / images / modules 
