# Contributing

Contributions can be made either through code or ticket input, both are equally valuable and 
help move the project forward.

It's worth noting that we do not follow traditional semantic versioning, but instead follow the
[WordPress approach to versions](https://make.wordpress.org/core/handbook/about/release-cycle/version-numbering/)
in that our major versions are X.X.Y, where X is a major version, and Y is a minor.


## Contributing through input

Tickets will often require opinions from various points of view, in regard to many aspects such as:
Design, accessibility, user experience, and language used, just to name a few.

When looking to implement features they are always added as issues first to allow others to provide
this kind of valuable input.


## Contributing with documentation
The directory `docs` has any documentation that for the plugin which will be included in a release, such as the readme and changelog.

Plugin assets, like screenshots and similar are found in the `assets` directory.

The handbook lives on the WordPress.org Support Team's site, at https://make.wordpress.org/support/handbook/appendix/troubleshooting-using-the-health-check/.


## Contributing with code

When contributing through code, please make sure each feature is developed as a separate, forked, branch of `trunk`.

The `trunk` branch is the most recent beta of the plugin at any given time, stable releases of the plugin may be downloaded from the [GitHub releases page](https://github.com/WordPress/health-check/releases), or from the [WordPress.org plugins page](https://wordpress.org/plugins/health-check/).

You do not need a local development environment set up to make code changes, although it is useful
when making changes to JavaScript or SASS (CSS styles) as these are concatenated by our build tools,
and are only provided in raw form in the repository.

The project has 3 primary directories:
- `src`, which contains general source files for the project.
- `tests`, where unit tests are created.
- `docs`, which houses the plugin readme, its changelog, and potentially any other documentation which may prove valuable.

Please do not change version numbers in when providing code changes, these are bumped by the project 
maintainers when a new version is released, and any changes outside of this may lead to confusion.


### Setting up a local environment

If you wish to set up a local environment for working with the project, start off by installing 
[node](https://nodejs.org), [npm](https://www.npmjs.com) (Node Package Manager), 
and [composer](https://getcomposer.org).

Once these are installed, you will want to open the command line in the project directory and
execute the following commands:
- `composer install` This will install composer dependencies, as defined in the `composer.json` file.
- `npm install` This will install node modules that we use, as defined in the `package.json` file.
- `npm run build` creates the `health-check` directory with all files for a finished plugin.

#### Docker setup
This project uses [wp-env](https://developer.wordpress.org/block-editor/packages/packages-env/) for it's Docker setup.

Once you have built the project, you may use the `npm run wp-env start` command to test your code. 

The `npm run wp-env` command is a placeholder, and you may pass any command that `wp-env` supports to it.

For convenience sake, you may also run `npm run watch` to automatically build any changes you make to the code during development.
 
### Submitting Pull Requests

Once you've got your development environment set up, and you are ready to push your code, here
are some items you should take note of
- Does the code follow the [WordPress Coding Standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/)?
- Did you include unit tests (if applicable)?

When pushing, you should use a branch name that is short and describes what your code does.
For example, if your code adds a feature for showing colors, naming it `feature-show-colors` makes
sense and is intuitive for any onlookers.

When your code has been committed and pushed to your fork, create a pull request.

The title of the pull request should be descriptive, summarize what your request is about in 5-10 words at most.
For the main body of the pull request, describe what you have done, and what problem this solves.
Include screenshots if possible to help visualize what changes have been introduced, and reference any open
issues if one exists.
