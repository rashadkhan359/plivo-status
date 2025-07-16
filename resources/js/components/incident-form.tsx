import { useState } from 'react';
import { Input } from './ui/input';
import { Button } from './ui/button';
import { Select } from './ui/select';
import { Label } from './ui/label';

const STATUS_OPTIONS = [
  { value: 'investigating', label: 'Investigating' },
  { value: 'identified', label: 'Identified' },
  { value: 'monitoring', label: 'Monitoring' },
  { value: 'resolved', label: 'Resolved' },
];
const SEVERITY_OPTIONS = [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
  { value: 'critical', label: 'Critical' },
];

export function IncidentForm({ initialValues = {}, onSubmit, serviceOptions = [], loading: loadingProp }: {
  initialValues?: { service_id?: string; title?: string; description?: string; status?: string; severity?: string };
  onSubmit: (values: any) => void;
  serviceOptions: { value: string; label: string }[];
  loading?: boolean;
}) {
  const [values, setValues] = useState({
    service_id: initialValues.service_id || '',
    title: initialValues.title || '',
    description: initialValues.description || '',
    status: initialValues.status || 'investigating',
    severity: initialValues.severity || 'low',
  });
  const [errors, setErrors] = useState<any>({});
  const [loading, setLoading] = useState(false);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    setValues((v) => ({ ...v, [e.target.name]: e.target.value }));
  };
  const handleStatusChange = (value: string) => {
    setValues((v) => ({ ...v, status: value }));
  };
  const handleSeverityChange = (value: string) => {
    setValues((v) => ({ ...v, severity: value }));
  };
  const handleServiceChange = (value: string) => {
    setValues((v) => ({ ...v, service_id: value }));
  };
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setErrors({});
    // Simple validation
    if (!values.title) {
      setErrors({ title: 'Title is required' });
      setLoading(false);
      return;
    }
    if (!values.service_id) {
      setErrors({ service_id: 'Service is required' });
      setLoading(false);
      return;
    }
    try {
      await onSubmit(values);
    } catch (err: any) {
      setErrors(err?.response?.data?.errors || { form: 'An error occurred' });
    } finally {
      setLoading(false);
    }
  };
  return (
    <form onSubmit={handleSubmit} className="space-y-4 max-w-md w-full">
      <div>
        <Label htmlFor="service_id">Service</Label>
        <Select value={values.service_id} onValueChange={handleServiceChange} disabled={loading || loadingProp}>
          <Select.Item value="" disabled>Select a service</Select.Item>
          {serviceOptions.map((opt) => (
            <Select.Item key={opt.value} value={opt.value}>{opt.label}</Select.Item>
          ))}
        </Select>
        {errors.service_id && <div className="text-red-500 text-xs mt-1">{errors.service_id}</div>}
      </div>
      <div>
        <Label htmlFor="title">Title</Label>
        <Input
          id="title"
          name="title"
          value={values.title}
          onChange={handleChange}
          disabled={loading || loadingProp}
          required
        />
        {errors.title && <div className="text-red-500 text-xs mt-1">{errors.title}</div>}
      </div>
      <div>
        <Label htmlFor="description">Description</Label>
        <Input
          id="description"
          name="description"
          value={values.description}
          onChange={handleChange}
          disabled={loading || loadingProp}
        />
      </div>
      <div className="flex gap-2">
        <div className="flex-1">
          <Label htmlFor="status">Status</Label>
          <Select value={values.status} onValueChange={handleStatusChange} disabled={loading || loadingProp}>
            {STATUS_OPTIONS.map((opt) => (
              <Select.Item key={opt.value} value={opt.value}>{opt.label}</Select.Item>
            ))}
          </Select>
        </div>
        <div className="flex-1">
          <Label htmlFor="severity">Severity</Label>
          <Select value={values.severity} onValueChange={handleSeverityChange} disabled={loading || loadingProp}>
            {SEVERITY_OPTIONS.map((opt) => (
              <Select.Item key={opt.value} value={opt.value}>{opt.label}</Select.Item>
            ))}
          </Select>
        </div>
      </div>
      {errors.form && <div className="text-red-500 text-xs mt-1">{errors.form}</div>}
      <Button type="submit" disabled={loading || loadingProp} className="w-full">
        {loading || loadingProp ? 'Saving...' : 'Save Incident'}
      </Button>
    </form>
  );
} 