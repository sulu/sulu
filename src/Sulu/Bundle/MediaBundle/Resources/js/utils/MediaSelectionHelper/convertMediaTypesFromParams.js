//@flow
export default function convertMediaTypesFromParams(types: ?string): Array<string> {
    if (!types) {
        return [];
    }

    return types.split(',').map((name) => {
        return name.trim();
    });
}
