import * as React from "react"
import { Slot } from "@radix-ui/react-slot"
import { cva, type VariantProps } from "class-variance-authority"

import { cn } from "@/lib/utils"

const badgeVariants = cva(
  "inline-flex items-center justify-center rounded-full border px-3 py-1.5 text-xs font-medium w-fit whitespace-nowrap shrink-0 [&>svg]:size-3 gap-1.5 [&>svg]:pointer-events-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive transition-all duration-200 ease-in-out overflow-auto shadow-sm backdrop-blur-sm",
  {
    variants: {
      variant: {
        default:
          "border-primary bg-primary text-primary-foreground shadow-sm hover:bg-primary/90",
        secondary:
          "border-border bg-secondary text-secondary-foreground shadow-sm hover:bg-secondary/80",
        destructive:
          "border-destructive bg-destructive text-white shadow-sm hover:bg-destructive/90",
        outline:
          "border-border bg-background text-foreground shadow-sm hover:bg-accent hover:text-accent-foreground",
        success:
          "border-green-200 bg-green-100 text-green-800 dark:border-green-800 dark:bg-green-900 dark:text-green-100 shadow-sm hover:bg-green-200 dark:hover:bg-green-800",
        warning:
          "border-yellow-200 bg-yellow-100 text-yellow-800 dark:border-yellow-800 dark:bg-yellow-900 dark:text-yellow-100 shadow-sm hover:bg-yellow-200 dark:hover:bg-yellow-800",
        info:
          "border-blue-200 bg-blue-100 text-blue-800 dark:border-blue-800 dark:bg-blue-900 dark:text-blue-100 shadow-sm hover:bg-blue-200 dark:hover:bg-blue-800",
        muted:
          "border-muted bg-muted text-muted-foreground shadow-sm hover:bg-muted/80",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  }
)

function Badge({
  className,
  variant,
  asChild = false,
  ...props
}: React.ComponentProps<"span"> &
  VariantProps<typeof badgeVariants> & { asChild?: boolean }) {
  const Comp = asChild ? Slot : "span"

  return (
    <Comp
      data-slot="badge"
      className={cn(badgeVariants({ variant }), className)}
      {...props}
    />
  )
}

export { Badge, badgeVariants }
