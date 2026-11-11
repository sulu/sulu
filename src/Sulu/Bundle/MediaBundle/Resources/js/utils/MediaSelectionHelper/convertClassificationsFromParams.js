//@flow
export default function convertClassificationsFromParams(classifications: ?string): Array<string> {
    if (!classifications) {
        return [];
    }

    return classifications.split(',').map((name) => {
        return name.trim();
    });
}
