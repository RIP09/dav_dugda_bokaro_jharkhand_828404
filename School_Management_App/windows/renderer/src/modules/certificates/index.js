// Certificates module (Windows app). Generic list/form screens come from modules/generic via the server's
// module registry; this file adds the certificates-specific screens, buttons and tabs.

export default {
  name: 'certificates',
  title: 'Certificates',
  keys: ["certificates"],
  screens: {},
  extensions: {
  certificates: { pdfDoc: 'certificate' },
},
};
