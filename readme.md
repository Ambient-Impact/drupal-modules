This respository contains Drupal modules that provide a framework for back-end
and front-end development which includes many PHP, JavaScript, and Sass
utilities, UX improvements and widgets, base styles, and libraries; for more
information, see [the component system
explainer](component_explainer.md).
These are used across multiple sites, including
[ambientimpact.com](https://ambientimpact.com/).

While not required for these modules to operate, the
[```ambientimpact_base```](https://gitlab.com/Ambient.Impact/drupal-themes)
theme integrates heavily with these modules.

**Warning**: while these are generally production-ready, they're not guaranteed
to maintain a stable API and may occasionally contain bugs, being a
work-in-progress. Stable releases may be provided at a later date.

----

# Requirements

* [Drupal 9.4 or newer](https://www.drupal.org/download) ([Drupal 8 is end-of-life](https://www.drupal.org/psa-2021-11-30))

* PHP 8

* [Composer](https://getcomposer.org/)

## Front-end dependencies

To build front-end assets for this project, [Node.js](https://nodejs.org/) and
[Yarn](https://yarnpkg.com/) are required.

----

# Installation

## Composer

This is a partly legacy codebase, and as such, Composer installation of the
modules in this repository isn't supported directly. You'll have to check out
the repository into your Drupal modules directory, optionally as a Git
submodule. The long term plan is to refactor these as individual Composer
packages, but for now, manual installation is required. That said, Composer is
recommended to install the various dependencies of these modules, which you can
do with the following.

Ensure that you have your Drupal installation set up with the correct Composer
installer types such as those provided by [the ```drupal\recommended-project```
template](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates#s-drupalrecommended-project).
If you're starting from scratch, simply requiring that template and following
[the Drupal.org Composer
documentation](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates)
should get you up and running.

[`cweagans/composer-patches`](https://github.com/cweagans/composer-patches) must
also be added to your project and set up before attempting to install these
modules as some of them provide patches for Drupal core and/or other modules
they depend on. If you're unsure, check each module's `composer.json` to see if
it defines any patches.

Then, in your root ```composer.json```, add the following to the
```"repositories"``` section. Note that you can leave out the modules you don't
plan to use.

```json
"drupal/ambientimpact_core": {
  "type": "path",
  "url": "<web directory>/modules/ambientimpact/ambientimpact_core"
},
"drupal/ambientimpact_icon": {
  "type": "path",
  "url": "<web directory>/modules/ambientimpact/ambientimpact_icon"
},
"drupal/ambientimpact_markdown": {
  "type": "path",
  "url": "<web directory>/modules/ambientimpact/ambientimpact_markdown"
},
"drupal/ambientimpact_media": {
  "type": "path",
  "url": "<web directory>/modules/ambientimpact/ambientimpact_media"
},
"drupal/ambientimpact_ux": {
  "type": "path",
  "url": "<web directory>/modules/ambientimpact/ambientimpact_ux"
}
```

where `<web directory>` is your public Drupal directory name, `web` by default.

Then, in your project's root, run ```composer require
"drupal/ambientimpact_core:6.x-dev@dev"``` to have Composer install the core
module and its required dependencies for you. Repeat the process for any other
modules you want to install dependencies for.

## Front-end assets

To build front-end assets for this project, you'll need to install
[Node.js](https://nodejs.org/) and [Yarn](https://yarnpkg.com/).

This package makes use of [Yarn
Workspaces](https://yarnpkg.com/features/workspaces) and references other local
workspace dependencies. In the `package.json` in the root of your Drupal
project, you'll need to add the following:

```json
"workspaces": [
  "<web directory>/modules/ambientimpact/*"
],
```

where `<web directory>` is your public Drupal directory name, `web` by default.
Once those are defined, add the following to the `"dependencies"` section of
your top-level `package.json`:

```json
"ambientimpact-drupal-modules": "workspace:^6"
```

Then run `yarn install` and let Yarn do the rest.

### Optional: install yarn.BUILD

While not required, we recommend installing [yarn.BUILD](https://yarn.build/) to
make building all of the front-end assets even easier.

### Optional: use ```nvm```

If you want to be sure you're using the same Node.js version we're using, we
support using [Node Version Manager (```nvm```)](https://github.com/nvm-sh/nvm)
([Windows port](https://github.com/coreybutler/nvm-windows)). Once ```nvm``` is
installed, you can simply navigate to the project root and run ```nvm install```
to install the appropriate version contained in the ```.nvmrc``` file.

Note that if you're using the [Windows
port](https://github.com/coreybutler/nvm-windows), it [does not support
```.nvmrc```
files](https://github.com/coreybutler/nvm-windows/wiki/Common-Issues#why-isnt-nvmrc-supported-why-arent-some-nvm-for-macoslinux-features-supported),
so you'll have to provide the version contained in the ```.nvmrc``` as a
parameter: ```nvm install <version>``` (without the ```<``` and ```>```).

This step is not required, and may be dropped in the future as Node.js is fairly
mature and stable at this point.

----

# Building front-end assets

We use [Webpack](https://webpack.js.org/) and [Symfony Webpack
Encore](https://symfony.com/doc/current/frontend.html) to automate most of the
build process. These will have been installed for you if you followed the Yarn
installation instructions above.

If you have [yarn.BUILD](https://yarn.build/) installed, you can run:

```
yarn build
```

from the root of your Drupal site. If you want to build just this package, run:

```
yarn workspace ambientimpact-drupal-modules run build
```

----

# Major breaking changes

The following major version bumps indicate breaking changes:

* 3.x - Some modules now require Drupal 9 and all development is now against that major version of Drupal.

* 4.x - Refactored to use [Sass modules](https://sass-lang.com/blog/the-module-system-is-launched); all development is now against this and will no longer compile using the old ```@import``` directive.

* 5.x - Now requires Drupal core 9.4.x; this is currently due to the patches in `ambientimpact_media` only applying against this core version.

* 6.x - Front-end dependencies now installed via [Yarn](https://yarnpkg.com/), removing all use of [Asset Packagist](https://asset-packagist.org/); front-end build process ported to [Webpack](https://webpack.js.org/).
