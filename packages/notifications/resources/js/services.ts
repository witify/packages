/**
 * Services the host application injects with configureNotifications(): the
 * package cannot import them from the host, and it must not bundle its own.
 */

export interface HttpClient {
  get(url: string, config?: unknown): Promise<{ data: any }>;
  post(url: string, data?: unknown, config?: unknown): Promise<{ data: any }>;
  patch(url: string, data?: unknown, config?: unknown): Promise<{ data: any }>;
  delete(url: string, config?: unknown): Promise<{ data: any }>;
}

export interface HttpOptions {
  showSnackbarOnError?: boolean;
  showSnackbarOnSuccess?: boolean;
}

export interface EchoClient {
  private(channel: string): { listen(event: string, callback: (payload: any) => void): unknown };
  leaveChannel(channel: string): void;
}

export interface NotificationsServices {
  http: HttpClient;
  /** A client that shows the host's snackbars on error or success; falls back to `http`. */
  useHttp?: (options?: HttpOptions) => HttpClient;
  /** Absent in applications without a WebSocket server: the inbox then refreshes on open only. */
  echo?: EchoClient;
  /** Id of the authenticated user, null for a guest. */
  currentUserId: () => number | string | null;
}

let configured: NotificationsServices | null = null;

export function configureNotifications(services: NotificationsServices): void {
  configured = services;
}

function services(): NotificationsServices {
  if (!configured) {
    throw new Error("@witify/notifications: call configureNotifications() before using the module.");
  }

  return configured;
}

export const http: HttpClient = {
  get: (url, config) => services().http.get(url, config),
  post: (url, data, config) => services().http.post(url, data, config),
  patch: (url, data, config) => services().http.patch(url, data, config),
  delete: (url, config) => services().http.delete(url, config),
};

export function useHttp(options?: HttpOptions): HttpClient {
  const factory = services().useHttp;

  return factory ? factory(options) : services().http;
}

export function echo(): EchoClient | undefined {
  return services().echo;
}

export function currentUserId(): number | string | null {
  return services().currentUserId();
}
