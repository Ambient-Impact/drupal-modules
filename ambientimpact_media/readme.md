This Drupal module contains various components and enhancements to Drupal core's
media entities and files.

**Warning**: while this is generally production-ready, it's not guaranteed to
maintain a stable API and may occasionally contain bugs, being a
work-in-progress. Stable releases may be provided at a later date.

----

# Requirements

* [Drupal 9.5](https://www.drupal.org/download) ([Drupal 8 is end-of-life](https://www.drupal.org/psa-2021-11-30))

* [Composer](https://getcomposer.org/)

## Drupal dependencies

Before attempting to install this, you must add the Composer repositories as
described in the installation instructions for these dependencies:

* The [`ambientimpact_core`](https://github.com/Ambient-Impact/drupal-ambientimpact-core), [`ambientimpact_icon`](https://github.com/Ambient-Impact/drupal-ambientimpact-icon), and [`ambientimpact_ux`](https://github.com/Ambient-Impact/drupal-ambientimpact-ux) modules.

## Front-end dependencies

To build front-end assets for this project, [Node.js](https://nodejs.org/) and
[Yarn](https://yarnpkg.com/) are required.

----

# Installation

## Composer

### Set up

Ensure that you have your Drupal installation set up with the correct Composer
installer types such as those provided by [the `drupal/recommended-project`
template](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates#s-drupalrecommended-project).
If you're starting from scratch, simply requiring that template and following
[the Drupal.org Composer
documentation](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates)
should get you up and running.

### Repository

In your root `composer.json`, add the following to the `"repositories"` section:

```json
"drupal/ambientimpact_media": {
  "type": "vcs",
  "url": "https://github.com/Ambient-Impact/drupal-ambientimpact-media.git"
}
```

### Patching

This provides [one or more patches](#patches). These can be applied automatically by the the
[`cweagans/composer-patches`](https://github.com/cweagans/composer-patches/tree/1.x)
Composer plug-in, but some set up is required before installing this module.
Notably, you'll need to [enable patching from
dependencies](https://github.com/cweagans/composer-patches/tree/1.x#allowing-patches-to-be-applied-from-dependencies) (such as this module 🤓). At
a minimum, you should have these values in your root `composer.json` (merge with
existing keys as needed):


```json
{
  "require": {
    "cweagans/composer-patches": "^1.7.0"
  },
  "config": {
    "allow-plugins": {
      "cweagans/composer-patches": true
    }
  },
  "extra": {
    "enable-patching": true,
    "patchLevel": {
      "drupal/core": "-p2"
    }
  }
}

```

**Important**: The 1.x version of the plug-in is currently required because it
allows for applying patches from a dependency; this is not implemented nor
planned for the 2.x branch of the plug-in.

### Installing

Once you've completed all of the above, run `composer require
"drupal/ambientimpact_media:1.x-dev@dev"` in the root of your project to have
 Composer install this and its required dependencies for you.

## Front-end assets

To build front-end assets for this project, you'll need to install
[Node.js](https://nodejs.org/) and [Yarn](https://yarnpkg.com/).

This package makes use of [Yarn
Workspaces](https://yarnpkg.com/features/workspaces) and references other local
workspace dependencies. In the `package.json` in the root of your Drupal
project, you'll need to add the following:

```json
"workspaces": [
  "<web directory>/modules/custom/*"
],
```

where `<web directory>` is your public Drupal directory name, `web` by default.
Once those are defined, add the following to the `"dependencies"` section of
your top-level `package.json`:

```json
"drupal-ambientimpact-media": "workspace:^1"
```

Then run `yarn install` and let Yarn do the rest.

### Optional: install yarn.BUILD

While not required, we recommend installing [yarn.BUILD](https://yarn.build/) to
make building all of the front-end assets even easier.

### Optional: use `nvm`

If you want to be sure you're using the same Node.js version we're using, we
support using [Node Version Manager (`nvm`)](https://github.com/nvm-sh/nvm)
([Windows port](https://github.com/coreybutler/nvm-windows)). Once `nvm` is
installed, you can simply navigate to the project root and run `nvm install` to
install the appropriate version contained in the `.nvmrc` file.

Note that if you're using the [Windows
port](https://github.com/coreybutler/nvm-windows), it [does not support `.nvmrc`
files](https://github.com/coreybutler/nvm-windows/wiki/Common-Issues#why-isnt-nvmrc-supported-why-arent-some-nvm-for-macoslinux-features-supported),
so you'll have to provide the version contained in the `.nvmrc` as a parameter:
`nvm install <version>` (without the `<` and `>`).

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
yarn workspace drupal-ambientimpact-media run build
```

# Patches

The following patches are supplied (see [Patching](#patching) above):

* Drupal core:

  * [Add a hook to modify oEmbed resource data [#3042423]](https://www.drupal.org/project/drupal/issues/3042423#comment-14902126) (requires Drupal core 9.5.3 or newer)

  * [Order image mappings by breakpoint ID and numeric multiplier [#3267870]](https://www.drupal.org/project/drupal/issues/3267870) (required for subsequent patch)

  * [Apply width and height attributes to responsive image tag [#3192234]](https://www.drupal.org/project/drupal/issues/3192234#comment-14510278)

* [Image Field Caption module](https://www.drupal.org/project/image_field_caption):

  * [Caption required incorrectly based on alt field required [#3181263]](https://www.drupal.org/project/image_field_caption/issues/3181263#comment-13895775)
