// @flow
export type Systems = {[system: string]: SecurityContextGroups};
export type SecurityContextGroups = {[securityContextGroup: string]: SecurityContexts};
export type SecurityContexts = {[securityContext: string]: Actions};
export type Actions = Array<string>;
