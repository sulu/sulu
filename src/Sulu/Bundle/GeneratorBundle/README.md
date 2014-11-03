# SuluGeneratorBundle

The SuluGeneratorBundle extends the default Symfony2 command line interface by providing new interactive and intuitive commands for generating code skeletons espacially for Sulu.

## Commands

* sulu:generate:bundle ... Generates a Bundle for Sulu 

### sulu:generate:bundle

Generates a Bundle for Sulu.

#### Special Parts

* Adminclass for SuluAdmin
* Required routing and service configuration files
* Configuration for special tools:
 * Composer
 * Travis
 * PHPUnit
 * Grunt
 * Git
* Public JavaScript files

#### Flow

1. /Command/GenerateBundleCommand ... Handles flow of commandline, starts generating bundle
1. /Generator/SuluBundleGenerator ... Generates Bundle structure and renders the required files
1. /Manipulator/KernelManipulator ... Manipulates the Kernel of the Project and add the new Bundle
1. /Manipulator/RoutingManipulator ... Manipulates the Routing of the Project and add the new Bundle for Routing

#### Structure

* /Command/* ... CommandLine Commands definitions
* /Generator/* ... Generators
* /Manipulator/* ... Manipulators
* /Resources/skeleton/bundle/* ... Skeleton for Bundle
* /Resources/skeleton/sulu/* ... Special Sulu File Templates


