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


## Contributing with code

When contributing through code, please start by forking the repository, and then making a clone 
for your self to work off.

You do not need a local development environment set up to make code changes, although it is useful
when making changes to JavaScript or SASS (CSS styles) as these are concatenated by our build tools,
and are only provided in raw form in the repository.

The project has 4 primary directories:
- `assets`, which holds JavaScript and SASS files.
- `bin`, which holds a shellscript to install the framework for running unit tests.
- `src`, which contains general source files for the project.
- `tests`, where unit tests are created. 

Please do not change version numbers in when providing code changes, these are bumped by the project 
maintainers when a new version is released, and any changes outside of this may lead to confusion.


### Setting up a local environment

If you wish to set up a local environment for working with the project, start off by installing 
[node](https://nodejs.org), [npm](https://www.npmjs.com) (Node Package Manager) 
and [composer](https://getcomposer.org).

Once these are installed, you will want to open the command line in the project directory and
execute the following commands:
- `composer install` This will install composer dependencies, as defined in the `composer.json` file.
- `npm install` This will install node modules that we use, as defined in the `package.json` file.


### Submitting Pull Requests

Once you've got your development environment set up, and you are ready to push your code, here
are some items you should take note of
- Does the code follow the [WordPress Coding Standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/)?
- Did you include unit tests (if applicable)?
- Was your local copy recently pulled from `master`, so it's a clean patch?

When pushing, you should use a branch name that is short and describes what your code does.
For example, if your code adds a feature for showing colors, naming it `feature-show-colors` makes
sense and is intuitive for any onlookers.

When your code has been committed and pushed to your fork, create a pull request.

The title of the pull request should be descriptive, summarize what your request is about in 5-10 words at most.
For the main body of the pull request, describe what you have done, and what problem this solves.
Include screenshots if possible to help visualize what changes have been introduced, and reference any open
issues if one exists.
