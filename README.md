This Moodle plugin adds a new predictive model to identify students that are likely to miss assignment due dates. The model automatically generates insights for teachers about these students.

This prediction model does not work for the following assignment activities:
- Assignments in courses without a start date
- Assignments without a due date
- Assignments that are hidden to students
- Not yet started assignments
- Team submission assignments
- Assignments whose due date are more than one year after the course start date
- Assignments whose due date are less than four days since the course start date

The plugin uses Moodle core's analytics API as a base. This predictive model indicators become available for the other predictive models in your site as well as for the predictive models you add in future

# Installation

https://docs.moodle.org/35/en/Installing_plugins

# Usage

https://docs.moodle.org/35/en/Analytics

# Known bugs

https://tracker.moodle.org/browse/MDL-64320 is a Moodle core bug that affects two of the indicators included in this plugin: "Number of assignment submission attempts" and "Activity weight in the gradebook". The latter was originally part of the prediction model that identifies students that are likely to miss an assignment due date. It has been removed due to MDL-64320. It will be added back once the issue is fixed in core.
