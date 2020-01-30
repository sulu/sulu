//@flow

export default class RequestPromise<T> extends Promise<T> {
    abortController: ?AbortController;

    setAbortController(abortController: ?AbortController) {
        this.abortController = abortController;
    }

    abort() {
        if (!this.abortController) {
            throw new Error('A request can only be aborted if the setAbortController function was called.');
        }
        this.abortController.abort();
    }

    then(onFulfilled: ?(*) => Promise<*> | *, onRejected: ?(*) => Promise<*> | *): RequestPromise<*> {
        const requestPromise: RequestPromise<*> = ((super.then(onFulfilled, onRejected): any): RequestPromise<*>);
        requestPromise.setAbortController(this.abortController);

        return requestPromise;
    }

    catch(onReject: ?(*) => Promise<*> | *): RequestPromise<*> {
        const requestPromise = ((super.catch(onReject): any): RequestPromise<*>);
        requestPromise.setAbortController(this.abortController);

        return requestPromise;
    }
}
