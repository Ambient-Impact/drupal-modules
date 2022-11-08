'use strict';

const glob = require('glob');
const path = require('path');
const pnp = require('pnpapi');

/**
 * Get all component directory paths found in the Yarn worktree.
 *
 * This queries Yarn's PnP API to find component directories within all packages
 * that are part of the worktree. This only works for packages that are
 * identified as workspaces via the project-level package.json 'workspaces'
 * field.
 *
 * @return {Object}
 *   An object containing both an 'all' key, which contains all component paths
 *   as a flat array, and component paths grouped by each package, keyed with
 *   the package name.
 *
 * @todo Further refine the dependency tree traversal to ensure we only glob
 *   from the lowest level packages, i.e. ones that don't have children?
 *
 * @see https://yarnpkg.com/features/workspaces
 *
 * @see https://yarnpkg.com/advanced/pnpapi
 *
 * @see https://yarnpkg.com/advanced/pnpapi#traversing-the-dependency-tree
 */
module.exports = function() {

  /**
   * The top-level package information object.
   *
   * @type {PackageInformation}
   *
   * @see https://yarnpkg.com/advanced/pnpapi#getpackageinformation
   */
  const topLevelWorkspace = pnp.getPackageInformation(pnp.getLocator(
    pnp.topLevel.name, pnp.topLevel.reference
  ));

  /**
   * The top level package name.
   *
   * This is used to avoid globbing from the top level package and instead only
   * glob from within the individual packages.
   *
   * @type {String}
   */
  const topLevelPackageName = pnp.findPackageLocator(
    topLevelWorkspace.packageLocation
  ).name;

  /**
   * Package paths, keyed by their package name.
   *
   * @type {Object}
   */
  let packagePaths = {};

  for (const locator of pnp.getDependencyTreeRoots()) {

    // Don't add the top level package as that would result in duplicate globbing
    // results and likely be a lot more expensive.
    if (locator.name === topLevelPackageName) {
      continue;
    }

    const packagePath = pnp.getPackageInformation(locator).packageLocation;

    // Themes are not yet supported for components, so ignore packages within a
    // 'theme' directory.
    if (packagePath.indexOf(`${path.sep}themes${path.sep}`) > -1) {
      continue;
    }

    packagePaths[locator.name] = packagePath;

  }

  /**
   * Component paths.
   *
   * @type {Object}
   *   An object containing both an 'all' key, which contains all component
   *   paths as a flat array, and component paths grouped by each package, keyed
   *   with the package name.
   */
  let componentPaths = {all: []};

  for (const packageName of Object.keys(packagePaths)) {

    let globbedPaths = glob.sync(
      '**/components', {cwd: packagePaths[packageName]}
    );

    if (typeof globbedPaths === 'undefined') {
      continue;
    }

    componentPaths[packageName] = globbedPaths.map(function(relativePath) {
      return path.join(packagePaths[packageName], relativePath);
    });

    componentPaths.all = componentPaths.all.concat(componentPaths[packageName]);

  }

  return componentPaths;

};
