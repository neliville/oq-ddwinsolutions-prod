/** Logs motion structurés — activer via localStorage.setItem('motion:debug', '1') */

const PREFIX = '[motion]';

export function isMotionDebug() {
    try {
        return typeof localStorage !== 'undefined' && localStorage.getItem('motion:debug') === '1';
    } catch {
        return false;
    }
}

export function motionLog(scope, message, data = undefined) {
    if (!isMotionDebug()) {
        return;
    }
    if (data !== undefined) {
        console.info(`${PREFIX} ${scope}:`, message, data);
    } else {
        console.info(`${PREFIX} ${scope}:`, message);
    }
}

export function motionWarn(scope, message, data = undefined) {
    if (!isMotionDebug()) {
        return;
    }
    if (data !== undefined) {
        console.warn(`${PREFIX} ${scope}:`, message, data);
    } else {
        console.warn(`${PREFIX} ${scope}:`, message);
    }
}
