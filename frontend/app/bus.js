import mitt from 'mitt'

const iroBus = mitt();

window._iro.bus = iroBus;

export default iroBus;
