function shouldOptimize() {

  const conn = navigator.connection;

  // 省流模式
  if (conn?.saveData) return false;

  // 弱网
  if (['slow-2g', '2g'].includes(conn?.effectiveType)) return false;

  // 弱设备
  if (navigator.deviceMemory && navigator.deviceMemory < 2) return false;

  // 低速
  if (conn?.downlink !== undefined && conn.downlink < 1) return false;

  // 无需优化
  return true;
}

_iro.optimize = shouldOptimize();

if(_iro.config.slow_net_optimize) {
}