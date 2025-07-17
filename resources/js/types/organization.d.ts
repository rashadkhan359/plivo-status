export type Organization = {
  id: number;
  name: string;
  slug: string;
  domain: string | null;
  created_at: string;
  updated_at: string;
  users_count?: number;
  services_count?: number;
  incidents_count?: number;
  maintenances_count?: number;
  users?: any[]; // Users when loaded
  services?: any[]; // Services when loaded
};
