# Hook Logger

## Overview

Hook Logger is a PrestaShop development tool for tracking and logging hook activations. This module facilitates monitoring display hooks and logging hook activations. Please note that this module is intended for development purposes and should not be installed in a production environment due to potential performance impacts.

## Installation

1. **Download and Install:**
   - Download the Hook Logger module.
   - Install the module through the PrestaShop back office.

2. **Configure Log File:**
   - Empty the log file located at `hooklogger/var/hooks_log.txt`. This file stores hook activation information.

3. **Usage:**
   - Perform actions within your PrestaShop environment.
   - Review the log file at `hooklogger/var/hooks_log.txt` to observe hook activations.

4. **Uninstallation:**
   - Uninstall the module using the PrestaShop back office.

## Features

### 1. Mark Display Hooks

Enable or disable marking display hooks in the PrestaShop front end via module settings. Marked hooks have a distinctive visual style, aiding identification during development.

### 2. Logging Options

Configure the module to log hook activations in different ways:
   - Log in a file: Activate logging to the file specified in `hooklogger/var/hooks_log.txt`.
   - Log with PrestaShopLogger: Utilize PrestaShop's built-in logger for hook activation information.

### 3. Configuration Settings

Access the module's settings through the PrestaShop back office. Customize Hook Logger behavior, including:
   - Marking of display hooks.
   - Logging options.
   - Other configurable parameters.

### 4. Compatibility Notes

- This module attaches to all hooks present in the database. Some hooks present in themes, modules, or classes may not be considered if they are not present in the hook table.

### 5. Customization

Modify the module as needed for specific requirements. Customization allows adaptation to unique development workflows.

## Compatibility

This module is compatible with PrestaShop versions 1.6 to 8.

---

**Note:** For optimal performance, refrain from using this module in a production environment. Modify the module according to specific needs and customization requirements.