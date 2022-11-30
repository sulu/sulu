// @flow
export default function(value: ?string): boolean {
    if (!value) {
        return false;
    }

    // used by webkit: https://github.com/WebKit/WebKit/blob/e205a844146bab749642d1b88974b706fe4d9e7e/Source/WebCore/html/EmailInputType.cpp#L38-L39
    // and recommend by the WHATWG: https://html.spec.whatwg.org/#valid-e-mail-address
    return /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/.test(value);
}
