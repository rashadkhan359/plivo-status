export type IncidentUpdate = {
  id: string;
  message: string;
  status: 'investigating' | 'identified' | 'monitoring' | 'resolved';
  created_at: string;
};
