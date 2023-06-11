This module provides a component system whose purpose is to organize code into
discrete packages that specialize in one thing or a collection of tightly
related things. The component system is primarily intended for front-end CSS,
JavaScript, HTML (by way of [Twig
templates](https://www.drupal.org/docs/8/theming/twig)), and other assets, but
component plug-ins can provide back-end functionality via their plug-in classes.

# Architecture

Components are implemented as [Drupal
plug-ins](https://www.drupal.org/docs/8/api/plugin-api), and as such, can make
use of most of the benefits of that system; for example, they can be defined by
any installed module and Drupal can automatically discover them. That said, the
component plug-in manager explicitly instantiates each component only once by
design and will fetch an existing instance if one exists for a given component.
Since components are intended primarily to package front-end assets and have a
1:1 relationship with their assets, there isn't a compelling reason to have more
than one instance per request; if such a need arises, other plug-in types can be
defined and used from within a component's methods.

# Structure

The bare minimum requirement to be recognized as a component is to have a
correctly defined class in the
```Drupal\<module_name>\Plugin\AmbientImpact\Component``` namespace.
Additionally, to provide front-end assets, a valid
```<component_name>.libraries.yml``` file must be found in the component's
directory. Both of these are detailed below. Other than the component plug-in
class (which must be in the
```<module_name>/src/Plugin\AmbientImpact\Component>``` directory), component
files are found in the ```<module_name>/components/<component_name>```
directory.

## Component plug-in class

The component plug-in class must extend
[```Drupal\ambientimpact_core\ComponentBase```](src/ComponentBase.php)
with the annotation structure defined in
[```Drupal\ambientimpact_core\Annotation\Component```](src/Annotation/Component.php).

If the only functionality of a component is to provide front-end assets, the
class can be empty (but must provide annotation data); an example of this can be
found in the [```MediaQuery```
class](ambientimpact_core/src/Plugin/AmbientImpact/Component/MediaQuery.php).
If a component needs additional back-end functionality and/or needs to pass
additional data to the front-end (via
[```drupalSettings```](https://www.drupal.org/docs/8/creating-custom-modules/adding-stylesheets-css-and-javascript-js-to-a-drupal-8-module#configurable)),
it can override several methods from ```ComponentBase``` and even define its own
custom methods; an example of these can be found in the [```PhotoSwipe```
component
class](ambientimpact_media/src/Plugin/AmbientImpact/Component/PhotoSwipe.php).

## Component libraries

As previously mentioned, components can provide their own libraries, in addition
to those defined by their implementing module. Libraries are defined in a
```<component_name>.libraries.yml``` file in the component's directory; [see the
Drupal documentation for the format of the libraries
file](https://www.drupal.org/docs/8/creating-custom-modules/adding-stylesheets-css-and-javascript-js-to-a-drupal-8-module).
The one important distinction is that all paths in the component's libraries
file are relative to the component's directory, not the implementing module's
directory. A simple example can be found in
[```media_query.libraries.yml```](components/media_query/media_query.libraries.yml).
