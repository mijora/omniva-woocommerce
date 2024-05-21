This folder is for the plugin to generate ReactJS files used by WP blocks. For this, the Node.js package manager "npm" is used.

Preparation for work:
Need to download the required libraries in this folder by executing the command "npm install".

Work progress:
The "src" folder contains files for editing. Changes made during editing are tested by activating the command "npm run start". This command continuously reads the files in the "src" folder and rebuilds the final files in the plugin "/assets/blocks" folder when changes are detected.

Completion of work:
The final files for the LIVE plugin are generated with the command "npm run build".
