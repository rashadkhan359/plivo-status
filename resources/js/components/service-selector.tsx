import React, { useState, useEffect } from 'react';
import { Check, ChevronsUpDown, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Badge } from '@/components/ui/badge';
import { Service } from '@/types/service';

interface ServiceSelectorProps {
    services: Service[];
    selectedServices: Service[];
    onSelectionChange: (services: Service[]) => void;
    placeholder?: string;
    disabled?: boolean;
    maxSelection?: number;
}

export function ServiceSelector({
    services,
    selectedServices,
    onSelectionChange,
    placeholder = "Select services...",
    disabled = false,
    maxSelection,
}: ServiceSelectorProps) {
    const [open, setOpen] = useState(false);
    const [searchValue, setSearchValue] = useState('');

    const availableServices = services.filter(
        service => !selectedServices.find(selected => selected.id === service.id)
    );

    const handleSelect = (service: Service) => {
        if (maxSelection && selectedServices.length >= maxSelection) {
            return;
        }
        
        const newSelection = [...selectedServices, service];
        onSelectionChange(newSelection);
        setSearchValue('');
    };

    const handleRemove = (serviceId: number) => {
        const newSelection = selectedServices.filter(service => service.id !== serviceId);
        onSelectionChange(newSelection);
    };

    const filteredServices = availableServices.filter(service =>
        service.name.toLowerCase().includes(searchValue.toLowerCase()) ||
        service.description?.toLowerCase().includes(searchValue.toLowerCase())
    );

    return (
        <div className="space-y-2">
            <div className="flex flex-wrap gap-2">
                {selectedServices.map((service) => (
                    <Badge
                        key={service.id}
                        variant="secondary"
                        className="flex items-center gap-1"
                    >
                        {service.name}
                        <button
                            type="button"
                            onClick={() => handleRemove(service.id)}
                            className="ml-1 rounded-full outline-none ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2"
                        >
                            <X className="h-3 w-3" />
                        </button>
                    </Badge>
                ))}
            </div>

            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        variant="outline"
                        role="combobox"
                        aria-expanded={open}
                        className="w-full justify-between"
                        disabled={disabled || (maxSelection ? selectedServices.length >= maxSelection : false)}
                    >
                        {selectedServices.length === 0
                            ? placeholder
                            : `${selectedServices.length} service${selectedServices.length === 1 ? '' : 's'} selected`}
                        <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-full p-0" align="start">
                    <Command>
                        <CommandInput
                            placeholder="Search services..."
                            value={searchValue}
                            onValueChange={setSearchValue}
                        />
                        <CommandList>
                            <CommandEmpty>No services found.</CommandEmpty>
                            <CommandGroup>
                                {filteredServices.map((service) => (
                                    <CommandItem
                                        key={service.id}
                                        value={service.name}
                                        onSelect={() => handleSelect(service)}
                                    >
                                        <Check
                                            className={cn(
                                                "mr-2 h-4 w-4",
                                                selectedServices.find(s => s.id === service.id)
                                                    ? "opacity-100"
                                                    : "opacity-0"
                                            )}
                                        />
                                        <div className="flex flex-col">
                                            <span className="font-medium">{service.name}</span>
                                            {service.description && (
                                                <span className="text-sm text-muted-foreground">
                                                    {service.description}
                                                </span>
                                            )}
                                        </div>
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
            
            {maxSelection && (
                <p className="text-xs text-muted-foreground">
                    {selectedServices.length}/{maxSelection} services selected
                </p>
            )}
        </div>
    );
} 