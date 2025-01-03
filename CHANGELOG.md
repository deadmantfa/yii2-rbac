CHANGELOG
=====================

v2.0.0
---------------------

* NEW: Full PHP 8+ compatibility.
* NEW: Enhanced type safety with property type declarations and return types.
* NEW: Refactored code for improved readability and maintainability.
* NEW: Unified logging methods for console commands (success, error, warning).
* NEW: Updated `RouteAccessControl` filter for better flexibility.
* UPDATED: README.md with modern instructions and compatibility notes.
* UPDATED: Enhanced GUI compatibility for Bootstrap 4 themes.
* UPDATED: Simplified roles and permissions initialization via CLI commands.
* FIXED: Bug with unused or redundant variables in core classes.
* FIXED: Improved `Routes Scanner` to handle edge cases in controllers and actions.

v1.2.1
---------------------

* Bootstrap4 support bug fix on Permission update screen.

v1.2
---------------------

* NEW: Bootstrap4 Themes support.
* NEW: RouteAccessControl filter autoCreatePermission option in debug mode.

v1.1.3
---------------------

* Bugfix: Compatibility with Adminlte v2.6.0 (conflict of $.fn.tree plugin name). @ap

v1.1.2
---------------------

* AccessControl filter update default regexp to ^site, in debug mode add gii and debug modules.

v1.1.1
---------------------

* Bugfix: Permission Child/Parents boxes cleanup available options from already exists.
* Bugfix: Permission Child/Parents fixed fatal error on hierarchy loop.

v1.1
---------------------

* NEW: Permissions selector as a real Tree-based selector.
* Bugfix: Fix wrong unique name validations for Role and Permission creating form.
* Bugfix: Fatal error on creating Role/Permission with existed name.

v1.0.2
---------------------

* Bugfix: Routes Scanner take info from comments as well, not class definition.

v1.0.1
---------------------

* Disable inherit permissions in role permissions selector.

v1.0
---------------------

* Rbac console command to init, assign, scan roles
* DbManager, PhpManager with auto master permission set
* Module with GUI interface to manage RBAC
* RouteAccessFilter to use as main access filter
