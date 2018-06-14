// @flow
import type {FinishFieldHandler} from '../types';

class HandlerRegistry {
    finishFieldHandlers: Array<FinishFieldHandler> = [];

    constructor() {
        this.clear();
    }

    clear() {
        this.finishFieldHandlers = [];
    }

    addFinishFieldHandler(handler: FinishFieldHandler) {
        this.finishFieldHandlers.push(handler);
    }

    getFinishFieldHandlers() {
        return this.finishFieldHandlers;
    }
}

export default new HandlerRegistry();
